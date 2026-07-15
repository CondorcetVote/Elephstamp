<?php

declare(strict_types=1);

namespace CondorcetVote\ElephStamp;

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Exception\InvalidInputException;
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
    public function __construct(private readonly DetachedTimestampFile $detached) {}

    /**
     * Load a receipt from the raw bytes of an `.ots` file.
     */
    public static function fromBytes(string $bytes): self
    {
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

        $bytes = file_get_contents($path);

        if ($bytes === false) {
            throw new InvalidInputException(\sprintf('Unable to read receipt file: %s', $path));
        }

        return self::fromBytes($bytes);
    }

    /**
     * Load a receipt from an already-open, readable `.ots` file handle.
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
        }

        return self::fromBytes($bytes);
    }

    /**
     * Serialize the receipt to the raw bytes of an `.ots` file.
     */
    public function toBytes(): string
    {
        return $this->detached->toBytes();
    }

    /**
     * Write the receipt to an `.ots` file on disk.
     *
     * @throws InvalidInputException if the file cannot be written
     */
    public function saveToPath(string $path): void
    {
        if (file_put_contents($path, $this->toBytes()) === false) {
            throw new InvalidInputException(\sprintf('Unable to write receipt file: %s', $path));
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
