<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Bitcoin\{AnchorLocator, BitcoinAnchor};
use CondorcetVote\ElephStamp\Exception\{InvalidInputException, SerializationException};
use CondorcetVote\ElephStamp\Operation\HashOperation;
use SplFileObject;

/**
 * A timestamp proof for one file — the object form of an `.ots` file.
 *
 * A receipt is created by {@see ElephStamp::stamp()} or loaded from existing
 * bytes. It is refreshed in place by {@see ElephStamp::upgrade()}, which merges
 * newly available blockchain attestations into it.
 */
final class Receipt
{
    /**
     * Hard cap on the size of an `.ots` input this library will load.
     *
     * Real proofs are a few kilobytes. An `.ots` file is untrusted input, and
     * the deserializer can allocate up to a few hundred bytes per input byte,
     * so the cap keeps a forged receipt from exhausting memory.
     */
    public const int MAX_RECEIPT_BYTES = 1_000_000;

    /**
     * The `.ots` file this receipt lives in: where it was loaded from, or
     * last saved to. Null for a receipt that never touched the disk.
     */
    public protected(set) ?string $path = null;

    public function __construct(private readonly DetachedTimestampFile $detached) {}

    /**
     * Load a receipt from the raw bytes of an `.ots` file.
     */
    public static function fromBytes(string $bytes): self
    {
        if (\strlen($bytes) > self::MAX_RECEIPT_BYTES) {
            throw new SerializationException(\sprintf('Receipt exceeds the maximum size of %d bytes', self::MAX_RECEIPT_BYTES));
        }

        return new self(DetachedTimestampFile::fromBytes($bytes));
    }

    /**
     * Load a receipt from an `.ots` file on disk.
     *
     * @throws InvalidInputException if the path is not a readable file
     */
    public static function fromPath(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidInputException(\sprintf('Receipt file does not exist or is not readable: %s', $path));
        }

        $size = filesize($path);

        if ($size !== false && $size > self::MAX_RECEIPT_BYTES) {
            throw new SerializationException(\sprintf('Receipt exceeds the maximum size of %d bytes', self::MAX_RECEIPT_BYTES));
        }

        $bytes = file_get_contents($path);

        if ($bytes === false) {
            throw new InvalidInputException(\sprintf('Unable to read receipt file: %s', $path));
        }

        $receipt = self::fromBytes($bytes);
        $receipt->path = $path;

        return $receipt;
    }

    /**
     * Load a receipt from an already-open, readable `.ots` file handle.
     *
     * The receipt remembers the file's path when the handle is a regular
     * file, so {@see save()} works on it.
     *
     * @throws InvalidInputException if the handle is not readable
     */
    public static function fromSplFileObject(SplFileObject $file): self
    {
        if (!$file->isReadable()) {
            throw new InvalidInputException('The provided SplFileObject is not readable');
        }

        $file->rewind();
        $bytes = '';

        while (!$file->eof()) {
            $chunk = $file->fread(1_048_576);

            if ($chunk === false) {
                throw new InvalidInputException('Failed while reading the receipt SplFileObject');
            }

            $bytes .= $chunk;

            if (\strlen($bytes) > self::MAX_RECEIPT_BYTES) {
                throw new SerializationException(\sprintf('Receipt exceeds the maximum size of %d bytes', self::MAX_RECEIPT_BYTES));
            }
        }

        $receipt = self::fromBytes($bytes);
        $realPath = $file->getRealPath();
        $receipt->path = $realPath === false ? null : $realPath;

        return $receipt;
    }

    /**
     * Serialize the receipt to the raw bytes of an `.ots` file.
     */
    public function toBytes(): string
    {
        return $this->detached->toBytes();
    }

    /**
     * Write the receipt back to the file it was loaded from or last saved to.
     *
     * @throws InvalidInputException if the receipt has no {@see $path} yet, or the file cannot be written
     */
    public function save(): void
    {
        if ($this->path === null) {
            throw new InvalidInputException('This receipt is not bound to a file yet; use saveToPath() first');
        }

        $this->saveToPath($this->path);
    }

    /**
     * Write the receipt to an `.ots` file on disk, and remember that path
     * for later {@see save()} calls.
     *
     * The bytes go through a temporary file renamed into place, so a crash
     * mid-write can never truncate an existing receipt — often the only copy
     * of a nonced commitment.
     *
     * @throws InvalidInputException if the file cannot be written
     */
    public function saveToPath(string $path): void
    {
        $bytes = $this->toBytes();
        $directory = \dirname($path);

        // tempnam() would silently fall back to the system temp directory
        // (breaking the atomic same-filesystem rename), so check first.
        $temporary = is_dir($directory) && is_writable($directory) ? tempnam($directory, '.ots.tmp.') : false;

        if ($temporary === false) {
            throw new InvalidInputException(\sprintf('Unable to write receipt file: %s', $path));
        }

        try {
            // tempnam() creates the file as 0600; align with the permissions a
            // plain file_put_contents() would have produced.
            @chmod($temporary, 0666 & ~umask());

            if (@file_put_contents($temporary, $bytes) !== \strlen($bytes) || !@rename($temporary, $path)) {
                throw new InvalidInputException(\sprintf('Unable to write receipt file: %s', $path));
            }

            $this->path = $path;
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function status(): Status
    {
        return $this->isComplete() ? Status::Complete : Status::Pending;
    }

    /**
     * Render the proof as an indented, human-readable string.
     *
     * Intended for inspection and debugging, not for parsing.
     */
    public function describe(): string
    {
        return \sprintf(
            "file %s digest: %s\n%s",
            $this->hashOperation()->describe(),
            $this->fileDigestHex(),
            $this->detached->timestamp->describe(),
        );
    }

    /**
     * Whether the timestamp is confirmed on the Bitcoin blockchain.
     */
    public function isComplete(): bool
    {
        return $this->detached->timestamp->hasBitcoinAttestation();
    }

    public function isPending(): bool
    {
        return !$this->isComplete();
    }

    /**
     * The raw digest of the timestamped file.
     */
    public function fileDigest(): string
    {
        return $this->detached->fileDigest();
    }

    /**
     * The digest of the timestamped file as a lower-case hex string.
     */
    public function fileDigestHex(): string
    {
        return bin2hex($this->detached->fileDigest());
    }

    public function hashOperation(): HashOperation
    {
        return $this->detached->fileHashOperation;
    }

    /**
     * The calendar URIs from which a completed proof can still be fetched.
     *
     * @return list<string>
     */
    public function pendingCalendarUris(): array
    {
        $uris = [];

        foreach ($this->detached->timestamp->allAttestations() as ['attestation' => $attestation]) {
            if ($attestation instanceof PendingAttestation) {
                $uris[$attestation->uri] = true;
            }
        }

        return array_keys($uris);
    }

    /**
     * Every Bitcoin attestation in the proof.
     *
     * @return list<BitcoinAttestation>
     */
    public function bitcoinAttestations(): array
    {
        $result = [];

        foreach ($this->detached->timestamp->allAttestations() as ['attestation' => $attestation]) {
            if ($attestation instanceof BitcoinAttestation) {
                $result[] = $attestation;
            }
        }

        return $result;
    }

    /**
     * Every Bitcoin attestation with what the proof reveals about it: the
     * block merkle root it commits to and, when recoverable, the transaction
     * carrying the commitment (hence a transaction id to look up).
     *
     * Like every Bitcoin figure this library reports, nothing is verified
     * against the blockchain.
     *
     * @return list<BitcoinAnchor>
     */
    public function bitcoinAnchors(): array
    {
        return AnchorLocator::locate($this->detached->timestamp);
    }

    /**
     * The lowest Bitcoin block height attesting the timestamp, or null if pending.
     *
     * This library does not verify the attestation against the blockchain; it
     * merely reports what the proof claims.
     */
    public function bitcoinBlockHeight(): ?int
    {
        $heights = array_map(static fn(BitcoinAttestation $a): int => $a->blockHeight, $this->bitcoinAttestations());

        return empty($heights) ? null : min($heights);
    }

    /**
     * The underlying detached timestamp, for advanced use.
     */
    public function detachedTimestampFile(): DetachedTimestampFile
    {
        return $this->detached;
    }
}
