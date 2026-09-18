<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\BitcoinAttestation;
use CondorcetVote\ElephStamp\Bitcoin\{AnchorLocator, BitcoinTransaction, TransactionParser};
use CondorcetVote\ElephStamp\Operation\{Prepend, Sha256};
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt, Timestamp};

/**
 * The transaction embedded in the reference hello-world proof, recovered once
 * for the whole file.
 */
function helloWorldTransaction(): BitcoinTransaction
{
    $anchors = Receipt::fromPath(__DIR__ . '/../fixtures/hello-world.txt.ots')->bitcoinAnchors();

    return $anchors[0]->transaction ?? throw new RuntimeException('fixture transaction not recovered');
}

it('recovers the transaction and merkle root of the reference proof', function (): void {
    $anchors = Receipt::fromPath(__DIR__ . '/../fixtures/hello-world.txt.ots')->bitcoinAnchors();

    // Both values checked against a block explorer for block 358391.
    expect($anchors)->toHaveCount(1)
        ->and($anchors[0]->blockHeight())->toBe(358_391)
        ->and($anchors[0]->attestation)->toBeInstanceOf(BitcoinAttestation::class)
        ->and($anchors[0]->merkleRootHex())->toBe('8a1b66ecb7cbd07d8139a7e7d7f2c41aab1f5009b8364aaf61d03ad245e47e00')
        ->and($anchors[0]->transaction?->txidHex())->toBe('7e9f0f7d9daa2d9e51b2e22f4abe814c3f90539afa778a9bef88dc64627cb2ec')
        ->and($anchors[0]->transaction?->size())->toBe(226)
        ->and($anchors[0]->transaction?->txid)->toBe(strrev(hex2bin('7e9f0f7d9daa2d9e51b2e22f4abe814c3f90539afa778a9bef88dc64627cb2ec')));
});

it('reports no anchor on a pending proof', function (): void {
    expect(Receipt::fromPath(__DIR__ . '/../fixtures/incomplete.txt.ots')->bitcoinAnchors())->toBe([]);
});

it('yields an anchor without transaction when the path embeds none', function (): void {
    // The fake calendar attaches the Bitcoin attestation straight onto the
    // commitment, with no transaction or merkle branch in between.
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('fake anchor'));
    $client->fakeCalendar()->confirmAll(700_000);
    $client->upgrade($receipt);

    $anchors = $receipt->bitcoinAnchors();

    expect($anchors)->toHaveCount(1)
        ->and($anchors[0]->blockHeight())->toBe(700_000)
        ->and($anchors[0]->transaction)->toBeNull()
        ->and($anchors[0]->merkleRoot)->not->toBeNull()
        ->and($anchors[0]->merkleRootHex())->toBe(bin2hex(strrev((string) $anchors[0]->merkleRoot)));
});

it('recovers a transaction that is alone in its block (no merkle branch)', function (): void {
    $transaction = helloWorldTransaction();

    // txid == merkle root: the attestation hangs right below the double SHA-256.
    $root = new Timestamp($transaction->rawBytes);
    $root->addOp(new Sha256)->addOp(new Sha256)->addAttestation(new BitcoinAttestation(1));

    $anchors = AnchorLocator::locate($root);

    expect($anchors)->toHaveCount(1)
        ->and($anchors[0]->transaction?->txidHex())->toBe($transaction->txidHex())
        ->and($anchors[0]->merkleRoot)->toBe($transaction->txid);
});

it('walks past merkle levels whose 64-byte nodes are not transactions', function (): void {
    $transaction = helloWorldTransaction();

    $root = new Timestamp($transaction->rawBytes);
    $node = $root->addOp(new Sha256)->addOp(new Sha256);

    for ($level = 0; $level < 5; ++$level) {
        $node = $node->addOp(new Prepend(str_repeat(\chr($level), 32)))->addOp(new Sha256)->addOp(new Sha256);
    }

    $node->addAttestation(new BitcoinAttestation(2));

    expect(AnchorLocator::locate($root)[0]->transaction?->txidHex())->toBe($transaction->txidHex());
});

it('accepts the smallest well-formed transaction and rejects malformed ones', function (): void {
    $version = "\x01\x00\x00\x00";
    $input = str_repeat("\x00", 36) . "\x00" . "\xff\xff\xff\xff";   // outpoint, empty script, sequence
    $output = str_repeat("\x00", 8) . "\x00";                          // value, empty script
    $locktime = "\x00\x00\x00\x00";
    $minimal = $version . "\x01" . $input . "\x01" . $output . $locktime;

    expect(\strlen($minimal))->toBe(60)
        ->and(TransactionParser::isTransaction($minimal))->toBeTrue()
        ->and(BitcoinTransaction::tryFromBytes($minimal)?->txid)->toBe(hash('sha256', hash('sha256', $minimal, binary: true), binary: true))
        // truncated, trailing garbage, no inputs, no outputs, script overrun
        ->and(TransactionParser::isTransaction(substr($minimal, 0, -1)))->toBeFalse()
        ->and(TransactionParser::isTransaction($minimal . "\x00"))->toBeFalse()
        ->and(TransactionParser::isTransaction($version . "\x00" . "\x01" . $output . $locktime))->toBeFalse()
        ->and(TransactionParser::isTransaction($version . "\x01" . $input . "\x00" . $locktime))->toBeFalse()
        ->and(TransactionParser::isTransaction($version . "\x01" . str_repeat("\x00", 36) . "\x10" . "\xff\xff\xff\xff" . "\x01" . $output . $locktime))->toBeFalse()
        ->and(TransactionParser::isTransaction(str_repeat("\xab", 64)))->toBeFalse()
        ->and(TransactionParser::isTransaction(''))->toBeFalse()
        ->and(BitcoinTransaction::tryFromBytes('nope'))->toBeNull();
});

it('handles multi-byte CompactSize counts and lengths', function (): void {
    $version = "\x02\x00\x00\x00";
    $script = str_repeat("\x51", 300);
    // 0xfd + uint16 LE for a 300-byte script.
    $input = str_repeat("\x00", 36) . "\xfd" . pack('v', 300) . $script . "\xff\xff\xff\xff";
    $output = str_repeat("\x00", 8) . "\x00";
    $transaction = $version . "\x01" . $input . "\x01" . $output . "\x00\x00\x00\x00";

    expect(TransactionParser::isTransaction($transaction))->toBeTrue()
        // A count claiming more items than there are bytes must fail cleanly.
        ->and(TransactionParser::isTransaction($version . "\xfe" . pack('V', 50_000) . $input . "\x01" . $output . "\x00\x00\x00\x00"))->toBeFalse()
        ->and(TransactionParser::isTransaction($version . "\xff" . pack('P', \PHP_INT_MAX) . $input . "\x01" . $output . "\x00\x00\x00\x00"))->toBeFalse();
});
