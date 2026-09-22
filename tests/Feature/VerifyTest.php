<?php

declare(strict_types=1);

use CondorcetVote\ElephStamp\Exception\InvalidInputException;
use CondorcetVote\ElephStamp\Verify\{AnchorOutcome, FakeBlockHeaderSource, Verdict, Verifier};
use CondorcetVote\ElephStamp\{ElephStamp, FileToStamp, Receipt};

function completedFakeReceipt(ElephStamp $client, string $content, int $height): Receipt
{
    $receipt = $client->stamp(FileToStamp::fromContent($content));
    $client->fakeCalendar()->confirm($receipt, $height);
    $client->upgrade($receipt);

    return $receipt;
}

it('verifies a receipt end to end in fake mode', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'verify me', 800_000);

    $client->fakeBlockSource()->anchor($receipt, new DateTimeImmutable('2024-06-01 12:00:00 UTC'));

    $report = $client->verify($receipt, FileToStamp::fromContent('verify me'));

    expect($report->verdict())->toBe(Verdict::Verified)
        ->and($report->isVerified())->toBeTrue()
        ->and($report->fileMatches)->toBeTrue()
        ->and($report->source)->toBe('fake block source')
        ->and($report->requiredConfirmations)->toBe(Verifier::DEFAULT_REQUIRED_CONFIRMATIONS)
        ->and($report->anchors)->toHaveCount(1)
        ->and($report->anchors[0]->outcome)->toBe(AnchorOutcome::Verified)
        ->and($report->anchors[0]->confirmations)->toBe(6)
        ->and($report->anchors[0]->header?->merkleRoot)->toBe($receipt->bitcoinAnchors()[0]->merkleRoot)
        ->and($report->attestedAt()?->format('Y-m-d H:i'))->toBe('2024-06-01 12:00')
        ->and($report->attestingAnchor()?->blockHeight())->toBe(800_000);
});

it('fails on a file that is not the one the proof commits to', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'the real file', 800_000);
    $client->fakeBlockSource()->anchor($receipt);

    $report = $client->verify($receipt, FileToStamp::fromContent('another file'));

    expect($report->verdict())->toBe(Verdict::Failed)
        ->and($report->fileMatches)->toBeFalse()
        // The chain check itself still passes: the proof is fine, just not for this file.
        ->and($report->anchors[0]->outcome)->toBe(AnchorOutcome::Verified)
        ->and($report->attestedAt())->toBeNull();
});

it('fails when the block does not commit to the proof', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'forged', 800_000);

    // Rewrite the chain after the fact: block 800000 no longer holds the root the proof leads to.
    $client->fakeBlockSource()->reset();
    $client->fakeBlockSource()->addBlock(800_000, str_repeat("\xee", 32));

    $report = $client->verify($receipt);

    expect($report->verdict())->toBe(Verdict::Failed)
        ->and($report->fileMatches)->toBeNull()
        ->and($report->anchors[0]->outcome)->toBe(AnchorOutcome::MerkleRootMismatch)
        ->and($report->anchors[0]->header)->not->toBeNull()
        ->and($report->anchors[0]->confirmations)->toBe(6);
});

it('waits for confirmations when the block is too recent', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'fresh', 800_000);
    $client->fakeBlockSource()->anchor($receipt);
    $client->fakeBlockSource()->setTipHeight(800_002);

    $report = $client->verify($receipt);

    expect($report->verdict())->toBe(Verdict::AwaitingConfirmations)
        ->and($report->anchors[0]->outcome)->toBe(AnchorOutcome::AwaitingConfirmations)
        ->and($report->anchors[0]->outcome->matches())->toBeTrue()
        ->and($report->anchors[0]->confirmations)->toBe(3)
        ->and($report->attestedAt())->toBeNull()
        // A lower threshold turns the same situation into a verification.
        ->and($client->verify($receipt, requiredConfirmations: 3)->verdict())->toBe(Verdict::Verified)
        ->and($client->verify($receipt, requiredConfirmations: 1)->anchors[0]->confirmations)->toBe(3);
});

it('is pending without any Bitcoin attestation', function (): void {
    $client = ElephStamp::fake();
    $receipt = $client->stamp(FileToStamp::fromContent('pending'));

    $report = $client->verify($receipt, FileToStamp::fromContent('pending'));

    expect($report->verdict())->toBe(Verdict::Pending)
        ->and($report->fileMatches)->toBeTrue()
        ->and($report->anchors)->toBe([]);
});

it('is inconclusive when the source knows nothing, and reports why', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'unknown block', 800_000);

    // A source that has never heard of the block the receipt names.
    $client->fakeBlockSource()->reset();
    $client->fakeBlockSource()->setTipHeight(900_000);

    $report = $client->verify($receipt);

    expect($report->verdict())->toBe(Verdict::Inconclusive)
        ->and($report->anchors[0]->outcome)->toBe(AnchorOutcome::BlockUnavailable)
        ->and($report->anchors[0]->error)->toContain('does not know block 800000');

    // No tip at all: every attestation is unavailable, nothing is thrown.
    $client->fakeBlockSource()->reset();

    $report = $client->verify($receipt);

    expect($report->verdict())->toBe(Verdict::Inconclusive)
        ->and($report->anchors[0]->error)->toContain('no block registered');
});

it('takes the earliest verified block as the attested date', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'earliest', 800_010);

    // Graft a second, earlier attestation onto the same receipt by hand.
    $pending = $receipt->detachedTimestampFile()->timestamp;
    $node = $pending->findPending() === [] ? null : $pending->findPending()[0]['node'];

    expect($node)->not->toBeNull();

    $earlier = new CondorcetVote\ElephStamp\Timestamp($node->msg);
    $earlier->addOp(new CondorcetVote\ElephStamp\Operation\Sha256)->addAttestation(new CondorcetVote\ElephStamp\Attestation\BitcoinAttestation(800_005));
    $node->merge($earlier);

    $blocks = $client->fakeBlockSource();
    $blocks->anchor($receipt, new DateTimeImmutable('2024-01-02 00:00:00 UTC'));
    $blocks->setTipHeight(800_020);

    $report = $client->verify($receipt);

    expect($report->anchors)->toHaveCount(2)
        ->and($report->count(AnchorOutcome::Verified))->toBe(2)
        ->and($report->attestingAnchor()?->blockHeight())->toBe(800_005)
        ->and($report->filter(AnchorOutcome::Verified))->toHaveCount(2);
});

it('rejects a confirmation threshold below one', function (): void {
    new Verifier(new FakeBlockHeaderSource, 0);
})->throws(InvalidInputException::class);

it('refuses to register two different merkle roots for one fake block', function (): void {
    $blocks = new FakeBlockHeaderSource;
    $blocks->addBlock(1, str_repeat("\x01", 32));
    $blocks->addBlock(1, str_repeat("\x01", 32)); // same root again is fine

    $blocks->addBlock(1, str_repeat("\x02", 32));
})->throws(InvalidInputException::class, 'distinct heights');

it('only exposes the fake block source in fake mode', function (): void {
    new ElephStamp()->fakeBlockSource();
})->throws(InvalidInputException::class, 'fake block source');

/**
 * Graft a second Bitcoin attestation, in $height, onto a receipt's pending node.
 */
function graftAttestation(Receipt $receipt, int $height): void
{
    $node = $receipt->detachedTimestampFile()->timestamp->findPending()[0]['node'];
    $branch = new CondorcetVote\ElephStamp\Timestamp($node->msg);
    $branch->addOp(new CondorcetVote\ElephStamp\Operation\Sha256)->addAttestation(new CondorcetVote\ElephStamp\Attestation\BitcoinAttestation($height));
    $node->merge($branch);
}

it('stays verified when another attestation does not match its block, and lists it', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'one good one bad', 800_000);
    graftAttestation($receipt, 800_001);

    // Block 800001 exists but holds something else entirely.
    $client->fakeBlockSource()->addBlock(800_001, str_repeat("\xee", 32));
    $client->fakeBlockSource()->setTipHeight(800_010);

    $report = $client->verify($receipt);

    expect($report->verdict())->toBe(Verdict::Verified)
        ->and($report->isVerified())->toBeTrue()
        ->and($report->count(AnchorOutcome::Verified))->toBe(1)
        ->and($report->mismatches())->toHaveCount(1)
        ->and($report->mismatches()[0]->blockHeight())->toBe(800_001)
        ->and($report->attestingAnchor()?->blockHeight())->toBe(800_000);
});

it('fails on a mismatch when nothing else vouches for the proof', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'bad and fresh', 800_000);
    graftAttestation($receipt, 800_001);

    $blocks = $client->fakeBlockSource();
    $blocks->reset();
    $blocks->addBlock(800_000, str_repeat("\xee", 32));
    $blocks->addBlock(800_001, $receipt->bitcoinAnchors()[1]->merkleRoot ?? '');
    $blocks->setTipHeight(800_002);

    // One mismatch, one merely awaiting confirmations: not proven, so failed.
    $report = $client->verify($receipt);

    expect($report->count(AnchorOutcome::AwaitingConfirmations))->toBe(1)
        ->and($report->mismatches())->toHaveCount(1)
        ->and($report->verdict())->toBe(Verdict::Failed);

    // Once the matching block is deep enough, the mismatch no longer matters.
    $blocks->setTipHeight(800_010);

    expect($client->verify($receipt)->verdict())->toBe(Verdict::Verified);
});

it('checks anchors in a batch through the verifier', function (): void {
    $client = ElephStamp::fake();
    $receipt = completedFakeReceipt($client, 'batch', 800_000);

    $verifier = new Verifier($client->fakeBlockSource());

    expect($verifier->checkAnchors([]))->toBe([])
        ->and($verifier->checkAnchors($receipt->bitcoinAnchors()))->toHaveCount(1)
        ->and($verifier->checkAnchors($receipt->bitcoinAnchors())[0]->outcome)->toBe(AnchorOutcome::Verified);
});
