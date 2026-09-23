<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation, TimeAttestation, UnknownAttestation};
use CondorcetVote\ElephStamp\Exception\SerializationException;
use CondorcetVote\ElephStamp\Operation\{Hexlify, Operation};
use CondorcetVote\ElephStamp\{DetachedTimestampFile, Receipt};
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/*
 * A corpus of hostile `.ots` files. An `.ots` is untrusted input (it comes
 * from a third party or from a calendar), so every guard the parser relies
 * on is exercised at its exact boundary, and corrupted or random inputs are
 * checked to fail with a SerializationException and nothing else: never a
 * PHP error, never an exception a caller would not expect from a parser.
 */

const HOSTILE_DIGEST_LENGTH = 32;

function hostileVaruint(int $value): string
{
    $bytes = '';

    do {
        $byte = $value & 0x7F;
        $value >>= 7;
        $bytes .= \chr($value > 0 ? $byte | 0x80 : $byte);
    } while ($value > 0);

    return $bytes;
}

/** A complete detached proof of a 32-byte zero digest (sha256) whose timestamp body is $body. */
function hostileProof(string $body): string
{
    return DetachedTimestampFile::HEADER_MAGIC . "\x01\x08" . str_repeat("\x00", HOSTILE_DIGEST_LENGTH) . $body;
}

function hostileAttestation(string $tag, string $payload): string
{
    return "\x00" . $tag . hostileVaruint(\strlen($payload)) . $payload;
}

function hostilePending(string $uri): string
{
    return hostileAttestation(PendingAttestation::TAG, hostileVaruint(\strlen($uri)) . $uri);
}

function hostileBitcoin(int $height): string
{
    return hostileAttestation(BitcoinAttestation::TAG, hostileVaruint($height));
}

function hostileAppend(string $argument): string
{
    return "\xf0" . hostileVaruint(\strlen($argument)) . $argument;
}

function hostilePrepend(string $argument): string
{
    return "\xf1" . hostileVaruint(\strlen($argument)) . $argument;
}

/**
 * Join sibling branches the way the format expects: every branch but the
 * last is announced by a 0xff continuation marker.
 *
 * @param list<string> $branches
 */
function hostileBranches(array $branches): string
{
    $last = array_pop($branches) ?? '';

    return ($branches === [] ? '' : "\xff" . implode("\xff", $branches)) . $last;
}

/** Random bytes, possibly none, which Randomizer::getBytes() refuses to produce. */
function hostileRandomBytes(Randomizer $randomizer, int $maxLength): string
{
    $length = $randomizer->getInt(0, $maxLength);

    return $length === 0 ? '' : $randomizer->getBytes($length);
}

/** @return array<string, list<string>> */
function hostileFixtures(): array
{
    $fixtures = [];

    foreach (glob(__DIR__ . '/../fixtures/*.ots') ?: [] as $path) {
        $fixtures[basename($path)] = [$path];
    }

    return $fixtures;
}

/**
 * Parse $bytes and report anything other than a clean parse or a
 * SerializationException, as "what went wrong" or null.
 */
function hostileParseOutcome(string $bytes): ?string
{
    try {
        Receipt::fromBytes($bytes);

        return null;
    } catch (SerializationException) {
        return null;
    } catch (Throwable $throwable) {
        return $throwable::class . ': ' . $throwable->getMessage();
    }
}

describe('depth', function (): void {
    it('accepts a chain of operations right under the recursion limit', function (): void {
        $receipt = Receipt::fromBytes(hostileProof(str_repeat("\x08", 255) . hostilePending('a')));

        expect($receipt->pendingCalendarUris())->toBe(['a']);
    });

    it('rejects a chain one level deeper', function (): void {
        Receipt::fromBytes(hostileProof(str_repeat("\x08", 256) . hostilePending('a')));
    })->throws(SerializationException::class, 'recursion depth');

    it('rejects a proof nested far beyond the limit before it can reach the stack', function (): void {
        Receipt::fromBytes(hostileProof(str_repeat("\x08", 200_000) . hostilePending('a')));
    })->throws(SerializationException::class, 'recursion depth');

    it('rejects a deep chain of growing messages before it can reach the stack', function (): void {
        Receipt::fromBytes(hostileProof(str_repeat(hostilePrepend('x'), 200_000) . hostilePending('a')));
    })->throws(SerializationException::class, 'recursion depth');
});

describe('width', function (): void {
    it('parses a proof holding as many attestations as the size cap allows', function (): void {
        $siblings = [];
        $size = \strlen(hostileProof(''));

        while (true) {
            $sibling = hostilePending(base_convert((string) \count($siblings), 10, 36));

            if ($size + \strlen($sibling) + 1 > Receipt::MAX_RECEIPT_BYTES) {
                break;
            }

            $siblings[] = $sibling;
            $size += \strlen($sibling) + 1;
        }

        $count = \count($siblings);
        $bytes = hostileProof(hostileBranches($siblings));
        $receipt = Receipt::fromBytes($bytes);

        expect($count)->toBeGreaterThan(50_000)
            ->and(\strlen($bytes))->toBeLessThanOrEqual(Receipt::MAX_RECEIPT_BYTES)
            ->and($receipt->pendingCalendarUris())->toHaveCount($count)
            ->and(Receipt::fromBytes($receipt->toBytes())->pendingCalendarUris())->toHaveCount($count);
    });

    it('parses a proof with tens of thousands of operation branches', function (): void {
        $count = 20_000;
        $branches = [];

        for ($i = 0; $i < $count; ++$i) {
            $branches[] = hostileAppend(pack('N', $i)) . hostileBitcoin($i);
        }

        $receipt = Receipt::fromBytes(hostileProof(hostileBranches($branches)));
        $timestamp = $receipt->detachedTimestampFile()->timestamp;

        expect($timestamp->operations())->toHaveCount($count)
            ->and($receipt->bitcoinAttestations())->toHaveCount($count)
            ->and(Receipt::fromBytes($receipt->toBytes())->bitcoinAttestations())->toHaveCount($count);
    });
});

describe('operation arguments', function (): void {
    it('accepts an append argument that fills the result cap exactly', function (): void {
        $argument = str_repeat('a', Operation::MAX_RESULT_LENGTH - HOSTILE_DIGEST_LENGTH);
        $receipt = Receipt::fromBytes(hostileProof(hostileAppend($argument) . hostilePending('a')));

        expect($receipt->pendingCalendarUris())->toBe(['a']);
    });

    it('rejects an append argument that overflows the result cap by one byte', function (): void {
        $argument = str_repeat('a', Operation::MAX_RESULT_LENGTH - HOSTILE_DIGEST_LENGTH + 1);
        Receipt::fromBytes(hostileProof(hostileAppend($argument) . hostilePending('a')));
    })->throws(SerializationException::class, 'Result too long');

    it('rejects an append argument above the argument cap', function (): void {
        Receipt::fromBytes(hostileProof(hostileAppend(str_repeat('a', Operation::MAX_RESULT_LENGTH + 1)) . hostilePending('a')));
    })->throws(SerializationException::class, 'exceeds maximum length');

    it('rejects an empty append argument', function (): void {
        Receipt::fromBytes(hostileProof(hostileAppend('') . hostilePending('a')));
    })->throws(SerializationException::class, 'shorter than minimum length');

    it('rejects a declared length in the terabytes without trying to read it', function (string $body): void {
        Receipt::fromBytes(hostileProof($body));
    })->throws(SerializationException::class, 'exceeds maximum length')->with([
        'append argument' => ["\xf0" . hostileVaruint(2 ** 40) . 'a'],
        'attestation payload' => ["\x00" . BitcoinAttestation::TAG . hostileVaruint(2 ** 40) . 'a'],
        'calendar uri' => [hostileAttestation(PendingAttestation::TAG, hostileVaruint(2 ** 40) . 'a')],
    ]);

    it('caps the message as it grows along a chain of prepends', function (): void {
        $fill = str_repeat('p', Operation::MAX_RESULT_LENGTH - HOSTILE_DIGEST_LENGTH);
        Receipt::fromBytes(hostileProof(hostilePrepend($fill) . hostilePrepend('q') . hostilePending('a')));
    })->throws(SerializationException::class, 'Result too long');

    it('lets hexlify double a message up to its own cap and not beyond', function (): void {
        $fill = str_repeat('h', Hexlify::MAX_MSG_LENGTH - HOSTILE_DIGEST_LENGTH);
        $receipt = Receipt::fromBytes(hostileProof(hostilePrepend($fill) . "\xf3" . hostilePending('a')));

        expect($receipt->pendingCalendarUris())->toBe(['a']);

        expect(static fn(): Receipt => Receipt::fromBytes(hostileProof(hostilePrepend($fill . 'h') . "\xf3" . hostilePending('a'))))
            ->toThrow(SerializationException::class, 'Message too long');
    });
});

describe('tags', function (): void {
    it('rejects an unknown operation tag inside the tree', function (): void {
        Receipt::fromBytes(hostileProof("\x08\x42" . hostilePending('a')));
    })->throws(SerializationException::class, 'Unknown operation tag 0x42');

    it('rejects a continuation marker with nothing after it', function (): void {
        Receipt::fromBytes(hostileProof(hostilePending('a') . "\xff"));
    })->throws(SerializationException::class);

    it('preserves an unknown attestation with a payload at the cap, byte for byte', function (): void {
        $tag = "\x11\x22\x33\x44\x55\x66\x77\x88";
        $bytes = hostileProof(hostileAttestation($tag, str_repeat("\xaa", TimeAttestation::MAX_PAYLOAD_SIZE)));
        $receipt = Receipt::fromBytes($bytes);
        $attestations = $receipt->detachedTimestampFile()->timestamp->allAttestations();

        expect($attestations)->toHaveCount(1)
            ->and($attestations[0]['attestation'])->toBeInstanceOf(UnknownAttestation::class)
            ->and($receipt->toBytes())->toBe($bytes);
    });

    it('rejects an unknown attestation whose payload exceeds the cap', function (): void {
        $tag = "\x11\x22\x33\x44\x55\x66\x77\x88";
        Receipt::fromBytes(hostileProof(hostileAttestation($tag, str_repeat("\xaa", TimeAttestation::MAX_PAYLOAD_SIZE + 1))));
    })->throws(SerializationException::class, 'exceeds maximum length');

    it('rejects a known attestation whose payload carries extra bytes', function (): void {
        Receipt::fromBytes(hostileProof(hostileAttestation(BitcoinAttestation::TAG, hostileVaruint(1) . "\x00")));
    })->throws(SerializationException::class, 'Trailing garbage');

    it('rejects a calendar URI over the length limit', function (): void {
        Receipt::fromBytes(hostileProof(hostilePending(str_repeat('a', PendingAttestation::MAX_URI_LENGTH + 1))));
    })->throws(SerializationException::class, 'exceeds maximum length');

    it('rejects a calendar URI with forbidden characters', function (string $uri): void {
        Receipt::fromBytes(hostileProof(hostilePending($uri)));
    })->throws(SerializationException::class, 'invalid character')->with([
        'newline' => ["https://a.example\n"],
        'space' => ['https://a.example /x'],
        'non-ascii' => ["https://\xc3\xa9.example"],
        'nul' => ["https://a.example\x00"],
    ]);
});

describe('corrupted genuine proofs', function (): void {
    it('rejects every truncation of a genuine proof with a SerializationException', function (string $path): void {
        $bytes = (string) file_get_contents($path);
        $accepted = [];
        $unexpected = [];

        for ($length = 0, $total = \strlen($bytes); $length < $total; ++$length) {
            $prefix = substr($bytes, 0, $length);

            try {
                Receipt::fromBytes($prefix);
                $accepted[] = $length;
            } catch (SerializationException) {
            } catch (Throwable $throwable) {
                $unexpected[$length] = $throwable::class . ': ' . $throwable->getMessage();
            }
        }

        expect($accepted)->toBe([])
            ->and($unexpected)->toBe([]);
    })->with(hostileFixtures());

    it('fails cleanly, or not at all, whatever byte of a genuine proof is corrupted', function (string $path): void {
        $bytes = (string) file_get_contents($path);
        $unexpected = [];

        for ($offset = 0, $total = \strlen($bytes); $offset < $total; ++$offset) {
            $original = \ord($bytes[$offset]);

            foreach ([$original ^ 0xFF, $original ^ 0x01, 0x00, 0xFF] as $replacement) {
                if ($replacement === $original) {
                    continue;
                }

                $mutated = $bytes;
                $mutated[$offset] = \chr($replacement);

                $outcome = hostileParseOutcome($mutated);

                if ($outcome !== null) {
                    $unexpected[\sprintf('%d:0x%02x', $offset, $replacement)] = $outcome;
                }
            }
        }

        expect($unexpected)->toBe([]);
    })->with(hostileFixtures());
});

describe('seeded fuzzing', function (): void {
    it('fails cleanly, or not at all, on random timestamp bodies built from the grammar', function (): void {
        $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'elephstamp-hostile-proof', true)));
        $unexpected = [];

        $tokens = [
            static fn(): string => "\xff",
            static fn(): string => "\x08",
            static fn(): string => "\x02",
            static fn(): string => "\x03",
            static fn(): string => "\x67",
            static fn(): string => "\xf2",
            static fn(): string => "\xf3",
            static fn() => hostileAppend(hostileRandomBytes($randomizer, 40)),
            static fn() => hostilePrepend(hostileRandomBytes($randomizer, 40)),
            static fn() => hostilePending(substr('https://abc.example/' . $randomizer->getBytes(4), 0, $randomizer->getInt(0, 24))),
            static fn() => hostileBitcoin($randomizer->getInt(0, 2_000_000)),
            static fn() => hostileAttestation($randomizer->getBytes(8), hostileRandomBytes($randomizer, 20)),
            static fn() => $randomizer->getBytes($randomizer->getInt(1, 8)),
        ];

        for ($round = 0; $round < 3_000; ++$round) {
            $body = '';
            $length = $randomizer->getInt(1, 60);

            for ($i = 0; $i < $length; ++$i) {
                $body .= $tokens[$randomizer->getInt(0, \count($tokens) - 1)]();
            }

            $outcome = hostileParseOutcome(hostileProof($body));

            if ($outcome !== null) {
                $unexpected[bin2hex($body)] = $outcome;
            }
        }

        expect($unexpected)->toBe([]);
    });

    it('fails cleanly, or not at all, on random bytes after a valid header', function (): void {
        $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'elephstamp-hostile-bytes', true)));
        $unexpected = [];

        for ($round = 0; $round < 3_000; ++$round) {
            $body = hostileRandomBytes($randomizer, 300);
            $outcome = hostileParseOutcome(hostileProof($body));

            if ($outcome !== null) {
                $unexpected[bin2hex($body)] = $outcome;
            }
        }

        expect($unexpected)->toBe([]);
    });
});
