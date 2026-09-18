# ElephStamp — library guide

This document covers the PHP API. For the `elephstamp` command-line tool, see
[CLI.md](CLI.md); for the generated class-by-class reference, see
[docs/readme.md](docs/readme.md).

## Contents

- [Quick start](#quick-start)
  - [Collecting every calendar's attestation](#collecting-every-calendars-attestation)
- [Describing what to stamp](#describing-what-to-stamp)
- [Reading a receipt](#reading-a-receipt)
  - [Locating the Bitcoin transaction](#locating-the-bitcoin-transaction)
- [Verifying against the blockchain](#verifying-against-the-blockchain)
  - [Choosing where block headers come from](#choosing-where-block-headers-come-from)
  - [Verifying in fake mode](#verifying-in-fake-mode)
- [Testing: fake mode](#testing-fake-mode)
- [Configuration](#configuration)
- [Exceptions](#exceptions)

## Quick start

### Stamp a file

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\FileToStamp;

$client = new ElephStamp();

$receipt = $client->stamp(FileToStamp::fromPath('contract.pdf'));

// Persist the proof next to your file.
$receipt->saveToPath('contract.pdf.ots');

echo $receipt->status()->name;        // "Pending"
echo $receipt->fileDigestHex();       // sha256 of contract.pdf
```

A freshly created timestamp is **pending**: the calendars have recorded your
commitment but Bitcoin has not confirmed it yet (this usually takes a few
hours).

All calendars are contacted **concurrently**, and a calendar that is
unreachable or misbehaves is tolerated: a stamp succeeds as long as at least
`requiredCalendars` of them accept it (see [Configuration](#configuration)).

### Follow up and collect the completed proof

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Receipt;

$client = new ElephStamp();

$receipt = Receipt::fromPath('contract.pdf.ots');

if ($client->upgrade($receipt)) {
    // Something changed: persist the richer proof.
    $receipt->saveToPath('contract.pdf.ots');
}

if ($receipt->isComplete()) {
    echo 'Anchored in Bitcoin block ' . $receipt->bitcoinBlockHeight();
}
```

`upgrade()` performs a single polling pass and returns whether the proof
changed. Call it again later while the receipt is still pending.

To learn what each calendar answered, use `upgradeWithReport()` instead. It
merges exactly like `upgrade()` but returns an `UpgradeReport` with one
`CalendarUpgradeResult` per pending attestation:

```php
use CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome;

$report = $client->upgradeWithReport($receipt);

foreach ($report->results as $result) {
    echo $result->calendarUrl, ': ', $result->outcome->name, "\n";
    // Upgraded | Unchanged | Pending | Failed | Rejected | Skipped
}

if ($report->changed()) {
    $receipt->saveToPath('contract.pdf.ots');
}

$report->count(UpgradeOutcome::Failed);   // how many calendars were unreachable
$report->filter(UpgradeOutcome::Skipped); // calendars not on the upgrade whitelist
```

`Failed` and `Rejected` results carry the calendar's error in `$result->error`;
`Upgraded`, `Unchanged` and `Confirmed` ones carry the lowest Bitcoin block
height now attested below that submission in `$result->blockHeight`.

### Collecting every calendar's attestation

One Bitcoin attestation is enough to make a receipt complete and verifiable
on its own, so by default a complete receipt is not polled again: `upgrade()`
returns `false` and `upgradeWithReport()` an empty report, even if the other
calendars have since anchored the commitment too. To collect their
attestations anyway (each calendar's transaction usually lands in a slightly
different block), pass `pollAll`:

```php
$report = $client->upgradeWithReport($receipt, pollAll: true);
// or: $client->upgrade($receipt, pollAll: true);

foreach ($report->results as $result) {
    // Confirmed: an attestation already hangs below this submission, the
    //            calendar was not asked again.
    // Upgraded:  a new attestation was fetched and merged.
    // Pending:   that calendar has still not anchored it.
}

count($receipt->bitcoinAnchors()); // one per branch that reached Bitcoin
```

The `.ots` format is a tree, so a receipt holding several Bitcoin
attestations stays perfectly interoperable; `bitcoinBlockHeight()` reports the
lowest of them.

## Describing what to stamp

`FileToStamp` has one explicit, typed constructor per source. Files are always
hashed as a stream — their content is never loaded into memory in full.

```php
use CondorcetVote\ElephStamp\FileToStamp;

FileToStamp::fromPath('invoice.pdf');                       // a file on disk
FileToStamp::fromSplFileObject(new SplFileObject('a.bin')); // an open handle
FileToStamp::fromContent('some in-memory string');          // raw bytes
FileToStamp::fromDigest($sha256);                           // a digest you already computed
```

`fromDigest()` uses the digest verbatim; its length must match the client's
hash operation (SHA-256 by default).

### Privacy nonce

By default a random nonce is mixed into the digest before it reaches a calendar,
so the calendar never learns the real file hash. When linkability is acceptable
— or desirable, e.g. so the commitment equals the file's plain SHA-256 and can
be recomputed without the `.ots` — disable it:

```php
FileToStamp::fromPath('public-release.zip')->withoutNonce();
```

Note that inside a `stampMany()` batch, a file stamped without nonce also
exposes its plain digest in the **sibling receipts** of the batch (the merkle
tree embeds each leaf's message into its neighbours' proofs) — not only to the
calendars.

### Stamping several files at once

`stampMany()` binds all files into a single merkle tree, so one calendar
submission covers them, while each file still gets its own independent receipt.

```php
$receipts = $client->stampMany(
    FileToStamp::fromPath('a.pdf'),
    FileToStamp::fromPath('b.pdf'),
    FileToStamp::fromPath('c.pdf'),
);
```

The returned list follows the order of the arguments, so you can zip it back
onto your own paths and persist each proof:

```php
$paths = ['a.pdf', 'b.pdf', 'c.pdf'];

$receipts = $client->stampMany(
    ...array_map(FileToStamp::fromPath(...), $paths),
);

foreach ($receipts as $i => $receipt) {
    $receipt->saveToPath($paths[$i] . '.ots');
}
```

Each receipt is a standalone `.ots`: it has to be upgraded and re-saved
individually, exactly like a single stamp. But they all descend from the same
commitment, so they turn complete at the same block:

```php
foreach ($paths as $path) {
    $receipt = Receipt::fromPath($path . '.ots');

    if ($client->upgrade($receipt)) {
        $receipt->saveToPath($path . '.ots');
    }
}
```

## Reading a receipt

```php
$receipt->status();               // Status::Pending | Status::Complete
$receipt->isComplete();
$receipt->isPending();
$receipt->fileDigest();           // raw digest bytes
$receipt->fileDigestHex();        // lower-case hex
$receipt->pendingCalendarUris();  // list<string> of calendars still to poll
$receipt->bitcoinBlockHeight();   // int|null (claimed, not verified)
$receipt->bitcoinAnchors();       // list<BitcoinAnchor>: block, merkle root, transaction (see below)
$receipt->toBytes();              // the raw .ots content
$receipt->describe();             // human-readable proof tree (for inspection)
```

### Locating the Bitcoin transaction

A proof never names the transaction that carries the commitment: it embeds
the transaction's raw bytes around the commitment and hashes them, then
follows the merkle branch up to the block's merkle root. `bitcoinAnchors()`
walks that path back for every Bitcoin attestation and recovers the values a
verifier compares with the chain:

```php
use CondorcetVote\ElephStamp\Receipt;

$receipt = Receipt::fromPath('contract.pdf.ots');

foreach ($receipt->bitcoinAnchors() as $anchor) {
    $anchor->blockHeight();                 // 967571
    $anchor->merkleRootHex();               // block merkle root, explorer byte order
    $anchor->transaction?->txidHex();       // transaction id, explorer byte order
    $anchor->transaction?->rawBytes;        // the witness-stripped transaction itself
}
```

`transaction` is `null` when the path does not contain a recognisable
transaction followed by a merkle branch (for example the attestations produced
by the fake calendar, or an unusual proof layout); `merkleRoot` is `null` below
an operation this library cannot compute. As always, nothing is checked
against the blockchain: the transaction id is what to look up in an explorer
or with `bitcoin-cli getrawtransaction`, and the merkle root what to compare
with `getblockheader`.

`describe()` renders the proof as an indented tree, handy for debugging:

```
file sha256 digest: da7badf6…f655
append d6c877a9…
sha256
 -> append 014870d0…
    sha256
    prepend f23482df…
    sha256
    pending attestation → https://finney.calendar.eternitywall.com
 -> …
```

## Verifying against the blockchain

A complete proof is a chain of computations from the file digest to the
merkle root of a Bitcoin block. Everything in it is recomputed locally; the
only external fact needed is the header of that block. `verify()` fetches it
through a `BlockHeaderSource` and compares merkle roots:

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\FileToStamp;
use CondorcetVote\ElephStamp\Receipt;
use CondorcetVote\ElephStamp\Verify\Verdict;

$client = new ElephStamp();   // asks mempool.space by default

$report = $client->verify(
    Receipt::fromPath('contract.pdf.ots'),
    FileToStamp::fromPath('contract.pdf'),   // optional: also check it is *this* file's proof
);

switch ($report->verdict()) {
    case Verdict::Verified:
        echo 'Existed before ', $report->attestedAt()->format(DATE_ATOM),
            ' (block ', $report->attestingAnchor()->blockHeight(), ')';
        break;
    case Verdict::AwaitingConfirmations:  // merkle root matches, block still too recent
    case Verdict::Pending:                // no Bitcoin attestation yet: upgrade first
    case Verdict::Inconclusive:           // the source could not answer
        break;
    case Verdict::Failed:                 // wrong file, or the block does not commit to the proof
        break;
}
```

The report holds `fileMatches` (null when no file was given), one
`AnchorVerification` per Bitcoin attestation with the block header, the
number of confirmations and an `AnchorOutcome` (`Verified`,
`AwaitingConfirmations`, `MerkleRootMismatch`, `BlockUnavailable`,
`NotComputable`), plus `requiredConfirmations` and the `source` consulted.
`verify()` never throws for a source failure: the affected attestations are
reported as unavailable.

A block counts once it is buried under six confirmations (`Verifier::DEFAULT_REQUIRED_CONFIRMATIONS`);
pass a third argument to change that. `Verified` needs a single attestation to
reach it; the attested date is the time of the earliest such block.

### Choosing where block headers come from

Block headers come from a `BlockHeaderSource`. The interface is neutral, so
the same verifier can be backed by a public explorer, a node, or a fake:

```php
use CondorcetVote\ElephStamp\Verify\CrossCheckingBlockHeaderSource;
use CondorcetVote\ElephStamp\Verify\EsploraBlockHeaderSource;
use CondorcetVote\ElephStamp\Verify\Explorer;
use CondorcetVote\ElephStamp\Verify\Verifier;

// One of the known public explorers (no API key needed).
$client = new ElephStamp(blockHeaderSource: Explorer::Blockstream->source());

// Any other Esplora-compatible instance, e.g. self-hosted (https only).
$client = new ElephStamp(blockHeaderSource: new EsploraBlockHeaderSource('https://esplora.internal/api'));

// Several sources that must all agree, to bound the trust put in any one of them.
$client = new ElephStamp(blockHeaderSource: new CrossCheckingBlockHeaderSource(
    Explorer::MempoolSpace->source(),
    Explorer::Blockstream->source(),
));

// The verifier alone, without the facade.
$report = new Verifier(Explorer::MempoolSpace->source(), requiredConfirmations: 1)->verify($receipt);
```

An explorer is a third party you trust for block headers. Two things limit
that trust: the Esplora driver fetches the raw 80-byte header, recomputes its
hash and checks its proof of work locally, so an explorer cannot serve a
bogus merkle root without forging a valid header; and cross-checking makes
several explorers vouch for the same header. Verifying against a Bitcoin node
you run is planned as another `BlockHeaderSource` implementation.

Explorer traffic is hardened like calendar traffic: https only, redirects
never followed, tiny response cap, timeouts on idle and total duration.

### Verifying in fake mode

`ElephStamp::fake()` comes with a `FakeBlockHeaderSource`. Register the blocks
a receipt claims, and verification works offline:

```php
$client = ElephStamp::fake();

$receipt = $client->stamp(FileToStamp::fromContent('hello'));
$client->fakeCalendar()->confirmAll(812_345);
$client->upgrade($receipt);

// Register a block 812345 whose merkle root is the one the receipt leads to.
$client->fakeBlockSource()->anchor($receipt, new DateTimeImmutable('2024-06-01 12:00 UTC'));

$client->verify($receipt)->verdict();   // Verdict::Verified

// Simulate a chain that has not grown yet, or a block that does not match.
$client->fakeBlockSource()->setTipHeight(812_346);        // → AwaitingConfirmations
$client->fakeBlockSource()->addBlock(812_345, $otherRoot); // throws: a fake height holds one root
```

## Testing: fake mode

`ElephStamp::fake()` returns a fully offline client. Proofs are deterministic
and the fake calendar lets you simulate Bitcoin confirmation, so you can test
both the pending and complete states without any network.

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\FileToStamp;

$client = ElephStamp::fake();

$receipt = $client->stamp(FileToStamp::fromContent('hello world'));
$receipt->isPending();   // true

// Simulate Bitcoin confirming the commitment.
$client->fakeCalendar()->confirmAll(blockHeight: 812_345);

$client->upgrade($receipt);
$receipt->isComplete();          // true
$receipt->bitcoinBlockHeight();  // 812345
```

Confirm a specific receipt, or everything submitted so far:

```php
use CondorcetVote\ElephStamp\Calendar\FakeCalendarClient;

$calendar = new FakeCalendarClient();
$client = ElephStamp::fake($calendar);

$receipt = $client->stamp(FileToStamp::fromContent('data'));

$calendar->confirm($receipt);  // confirm just this receipt
// or
$calendar->confirmAll();       // confirm every commitment submitted so far
```

## Configuration

```php
use CondorcetVote\ElephStamp\ElephStamp;

$client = new ElephStamp(
    calendarUrls: ['https://a.pool.opentimestamps.org', 'https://b.pool.opentimestamps.org'],
    requiredCalendars: 2, // the "m" of an m-of-n policy
);
```

Calendar URLs must be unique and use **https** (a plaintext connection would
let a network attacker inject forged responses); the same goes for whitelist
patterns. When `requiredCalendars` is omitted it defaults to **2** — like the
reference client — or to 1 when a single calendar is configured.

### Upgrade whitelist (security)

An `.ots` proof embeds the calendar URIs to poll when upgrading. Because a proof
may come from an untrusted source, `upgrade()` only contacts hosts on an
allowlist — otherwise a hostile proof could point the process at arbitrary hosts
(an SSRF risk). The default covers the known public operators
(`*.calendar.opentimestamps.org`, `*.calendar.eternitywall.com`,
`*.calendar.catallaxy.com`). Override it when you use private calendars:

```php
$client = new ElephStamp(
    calendarUrls: ['https://ots.internal.example'],
    upgradeWhitelist: ['https://*.internal.example'],
);
```

Host patterns accept shell-style globs; URLs with a query, fragment or
credentials are always rejected.

### Customising the HTTP client

The default transport is the Symfony HTTP client, hardened for calendar
traffic: redirects are never followed, responses are capped at 10 kB, and
requests time out after 10 s of silence (30 s in total). Tune the timeouts, or
inject your own configured instance (proxy, retries, ...) when needed:

```php
use CondorcetVote\ElephStamp\Calendar\HttpCalendarClient;
use CondorcetVote\ElephStamp\ElephStamp;
use Symfony\Component\HttpClient\HttpClient;

$calendarClient = new HttpCalendarClient(
    HttpClient::create(['proxy' => 'http://proxy.internal:3128']),
    timeout: 5.0,       // idle timeout, seconds
    maxDuration: 15.0,  // hard cap per request, seconds
);

$client = new ElephStamp(calendarClient: $calendarClient);
```

## Exceptions

Every exception implements `CondorcetVote\ElephStamp\Exception\ElephStampException`:

- `InvalidInputException` — bad caller input (unreadable file, invalid options).
- `SerializationException` — malformed or unsupported `.ots` data.
- `CalendarException` — a calendar was unreachable or misbehaved.
- `StampingException` — too few calendars accepted a stamp (m-of-n not met).
- `BlockSourceException` — a block header source was unreachable, knows no
  such block, or answered inconsistently. `verify()` catches it for you and
  reports the attestation as unavailable; you only meet it when calling a
  `BlockHeaderSource` directly.
