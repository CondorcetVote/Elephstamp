<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Attestation\{BitcoinAttestation, PendingAttestation};
use CondorcetVote\ElephStamp\Console\Inspection\SubmissionPoint;
use CondorcetVote\ElephStamp\Operation\{Append, Prepend, Sha256};
use CondorcetVote\ElephStamp\{Receipt, Timestamp};

function calendarBranch(Timestamp $submitted, string $nonce, string $uri): void
{
    $submitted->addOp(new Append($nonce))->addOp(new Sha256)->addAttestation(new PendingAttestation($uri));
}

it('finds the digest submitted after the privacy nonce', function (): void {
    $root = Receipt::fromPath(__DIR__ . '/../fixtures/two-calendars.txt.ots')->detachedTimestampFile()->timestamp;

    $point = SubmissionPoint::locate($root);

    expect($point)->not->toBeNull()
        ->and(bin2hex((string) $point?->node->msg))->toBe('679a59f6661f9d809d6f72d2cc080a20435c5c793ace1961ca78e38693f2f53d')
        ->and($point?->isFileDigest())->toBeFalse()
        ->and($point?->isBatchRoot())->toBeFalse()
        ->and($point?->nonce?->argument)->toBe(hex2bin('839037eef449dec6dac322ca97347c45'));
});

it('finds the merkle root submitted for a batch', function (): void {
    $root = Receipt::fromPath(__DIR__ . '/../fixtures/merkle2.txt.ots')->detachedTimestampFile()->timestamp;

    $point = SubmissionPoint::locate($root);

    expect($point?->digestHex())->toBe('c8f78972680bec45199bf6f19472fe1db32ceeed4509c79f345086bf4888d3fa')
        ->and($point?->isBatchRoot())->toBeTrue()
        ->and($point?->nonce?->argument)->toBe(hex2bin('b63d8f213d047298b8ab4595acd8e5d0'));
});

it('recognises the file digest itself when no nonce was used', function (): void {
    $root = new Timestamp(hash('sha256', 'file A', true));
    calendarBranch($root, str_repeat("\x01", 16), 'https://a.example');
    calendarBranch($root, str_repeat("\x02", 8), 'https://b.example');

    $point = SubmissionPoint::locate($root);

    expect($point?->node)->toBe($root)
        ->and($point?->isFileDigest())->toBeTrue()
        ->and($point?->isBatchRoot())->toBeFalse()
        ->and($point?->nonce)->toBeNull();
});

it('flags no nonce when the first operation is not a 16-byte append', function (): void {
    $root = new Timestamp(hash('sha256', 'file A', true));
    $submitted = $root->addOp(new Prepend(str_repeat("\x03", 16)))->addOp(new Sha256);
    calendarBranch($submitted, str_repeat("\x01", 16), 'https://a.example');
    calendarBranch($submitted, str_repeat("\x02", 16), 'https://b.example');

    $point = SubmissionPoint::locate($root);

    expect($point?->node)->toBe($submitted)
        ->and($point?->nonce)->toBeNull()
        ->and($point?->isBatchRoot())->toBeTrue();
});

it('does not guess on a single calendar branch', function (string $fixture): void {
    $root = Receipt::fromPath(__DIR__ . '/../fixtures/' . $fixture)->detachedTimestampFile()->timestamp;

    expect(SubmissionPoint::locate($root))->toBeNull();
})->with(['incomplete.txt.ots', 'hello-world.txt.ots']);

it('does not treat a node carrying a single attestation as the submission point', function (): void {
    $root = new Timestamp(hash('sha256', 'file A', true));
    $commitment = $root->addOp(new Append(str_repeat("\x01", 16)))->addOp(new Sha256);
    // An upgraded single calendar: its pending attestation stays next to the bitcoin path.
    $commitment->addAttestation(new PendingAttestation('https://a.example'));
    $commitment->addOp(new Sha256)->addAttestation(new BitcoinAttestation(800_000));

    expect(SubmissionPoint::locate($root))->toBeNull();
});

it('finds a digest several calendars attested directly', function (): void {
    $root = new Timestamp(hash('sha256', 'file A', true));
    $submitted = $root->addOp(new Append(str_repeat("\x01", 16)))->addOp(new Sha256);
    $submitted->addAttestation(new PendingAttestation('https://a.example'));
    $submitted->addAttestation(new PendingAttestation('https://b.example'));

    expect(SubmissionPoint::locate($root)?->node)->toBe($submitted);
});
