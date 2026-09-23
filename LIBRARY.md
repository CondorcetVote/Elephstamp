# ElephStamp — library guide

This document covers the PHP API. For the `elephstamp` command-line tool, see
[CLI.md](CLI.md); for the generated class-by-class reference, see
[docs/readme.md](docs/readme.md).

## Contents

- [Quick start](#quick-start)
  - [Checking answers before merging them](#checking-answers-before-merging-them)
  - [Collecting every calendar's attestation](#collecting-every-calendars-attestation)
- [Describing what to stamp](#describing-what-to-stamp)
  - [Hash algorithm](#hash-algorithm)
- [Reading a receipt](#reading-a-receipt)
  - [Saving](#saving)
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
    // Something changed: persist the richer proof where it came from.
    $receipt->save();
}

if ($receipt->isComplete()) {
    echo 'Anchored in Bitcoin block ' . $receipt->bitcoinBlockHeight();
}
```

`upgrade()` performs a single polling pass and returns whether the proof
changed. Call it again later while the receipt is still pending.

A calendar's answer is **checked against the blockchain before it is merged**
(see [below](#checking-answers-before-merging-them)): an attestation naming a
block that does not commit to the proof, or a block too recent to be final,
is not merged and the calendar stays pending. This needs a block header
source; without configuration it is the mempool.space explorer, exactly as
for `verify()`.

To learn what each calendar answered, use `upgradeWithReport()` instead. It
merges exactly like `upgrade()` but returns an `UpgradeReport` with one
`CalendarUpgradeResult` per pending attestation:

```php
use CondorcetVote\ElephStamp\Upgrade\UpgradeOutcome;

$report = $client->upgradeWithReport($receipt);

foreach ($report->results as $result) {
    echo $result->calendarUrl, ': ', $result->outcome->name, "\n";
    // Upgraded | Unchanged | Pending | Failed | Rejected | Unconfirmed | Unverifiable | Skipped
}

if ($report->changed()) {
    $receipt->save();
}

$report->count(UpgradeOutcome::Failed);   // how many calendars were unreachable
$report->filter(UpgradeOutcome::Skipped); // calendars not on the upgrade whitelist
```

`Failed`, `Rejected`, `Unconfirmed` and `Unverifiable` results carry the
reason in `$result->error`; `Upgraded`, `Unchanged` and `Confirmed` ones carry
the lowest Bitcoin block height now attested below that submission in
`$result->blockHeight`. Every result also exposes what the check of the
calendar's answer gave, when there was something to check:

```php
$result->verifications;        // list<AnchorVerification>: one per Bitcoin attestation in the answer
$result->verified();           // true, false, or null when the answer carried no Bitcoin attestation
$result->confirmations();      // depth of the shallowest block named, or null
$result->claimedBlockHeight(); // the block the answer names, merged or not
$report->blockHeaderSource;    // "mempool.space", or null when nothing needed checking
```

### Checking answers before merging them

An `.ots` is only as good as its weakest branch, and a calendar is a third
party: a buggy or hostile one could return an attestation naming a block that
does not commit to your proof, or a block that has just been mined and may
still be reorganised away. Merged blindly, either would make the receipt
"complete" while `verify()` fails it, and once a receipt is complete the
other calendars are no longer polled by default, so the damage would stick.

So `upgrade()` and `upgradeWithReport()` verify every Bitcoin attestation a
calendar returns exactly like `verify()` does, through the configured
`BlockHeaderSource`, **before** anything is merged. Only a fully verified
answer becomes part of the receipt:

| Outcome | What the check found | Merged? |
| --- | --- | --- |
| `Upgraded` / `Unchanged` | Every attestation in the answer matches its block, buried under enough confirmations. | yes |
| `Rejected` | A block's merkle root differs from the one the answer leads to (or the answer is for another digest). Hostile or corrupt. | no |
| `Unconfirmed` | The merkle root matches but the block is still shallower than required. Poll again later. | no |
| `Unverifiable` | The source could not provide the header (unreachable, unknown block), or the attestation sits below an operation this library cannot compute. | no |

The calendar stays pending in the receipt whenever its answer is not merged,
so the next pass asks it again. The chain tip is fetched once per pass, then
one header per attestation, so an upgrade costs at most one explorer request
more than there are answers to check.

```php
// Default: verify, and require six confirmations like verify() does.
$client->upgrade($receipt);

// Accept shallower blocks.
$client->upgrade($receipt, requiredConfirmations: 1);

// Merge whatever the calendars return, unchecked, like the reference client.
$client->upgrade($receipt, verify: false);
```

Verification is skipped, and no explorer contacted, when an answer carries
no Bitcoin attestation (still pending, or intermediate operations only).
Pick the source with the `blockHeaderSource` constructor argument, see
[Choosing where block headers come from](#choosing-where-block-headers-come-from);
a custom `CalendarClient` used in tests should come with a
`FakeBlockHeaderSource` holding the blocks it will name, or upgrade with
`verify: false`.

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
FileToStamp::fromDigest(hex2bin($hex));                      // a digest you already computed
```

`fromDigest()` takes the **raw digest bytes**, not their hex spelling, and uses
them verbatim: nothing is hashed again. Their length must match the client's
hash operation (SHA-256 by default, see [Hash algorithm](#hash-algorithm)),
which is checked when the client stamps or verifies.

### Hash algorithm

The `.ots` format names the algorithm a proof commits to a file with. The
client hashes with **SHA-256**, like the reference client; the format also
supports SHA-1 and RIPEMD-160, which the reference tooling reads and verifies
as well. Choose one with the `hashOperation` constructor argument. It applies
to every source, `fromDigest()` included, and is written into the proof:

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\Operation\{HashOperation, Sha1};

$client = new ElephStamp(hashOperation: new Sha1);
// or, from a name given by your users: sha256, sha1 or ripemd160
$client = new ElephStamp(hashOperation: HashOperation::fromName('sha1'));

$receipt = $client->stamp(FileToStamp::fromDigest(sha1('report.pdf content', binary: true)));
$receipt->hashOperation()->describe();   // "sha1"
```

`HashOperation::names()` lists the accepted names; `fromName()` throws an
`InvalidInputException` for anything else, Keccak-256 included (PHP has no
implementation of it). Reading, upgrading and verifying a proof never need
this setting: a `Receipt` carries its own algorithm, `hashOperation()`.

The privacy nonce is always mixed in with SHA-256, whatever the file hash, so
the calendars receive a 32-byte commitment either way. Without a nonce, a
single SHA-1 or RIPEMD-160 commitment reaches them as its 20 bytes, which the
public calendars accept.

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
        $receipt->save();
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
$receipt->path;                   // the .ots file it was loaded from or last saved to, or null
```

### Saving

A receipt remembers its file: `Receipt::fromPath()` (and
`fromSplFileObject()` on a regular file) records where it came from, and
`saveToPath()` records where it went. `save()` then writes back there, so the
usual upgrade loop needs no path bookkeeping:

```php
$receipt = Receipt::fromPath('contract.pdf.ots');

if ($client->upgrade($receipt)) {
    $receipt->save();                       // rewrites contract.pdf.ots
}

$receipt->saveToPath('archive/contract.pdf.ots');
$receipt->path;                             // now 'archive/contract.pdf.ots'
```

`save()` throws an `InvalidInputException` on a receipt that never touched
the disk (fresh from `stamp()` or `fromBytes()`): give it a path with
`saveToPath()` first. Writes are atomic: the bytes go through a temporary
file renamed into place, so a crash mid-write never truncates an existing
receipt. `path` is read-only from outside the class.

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
from a block explorer, or from a Bitcoin node you run, and compares merkle
roots. **By default the explorer is mempool.space**; see
[below](#choosing-where-block-headers-come-from) to pick another source.

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\FileToStamp;
use CondorcetVote\ElephStamp\Receipt;
use CondorcetVote\ElephStamp\Verify\Verdict;

$client = new ElephStamp();

$report = $client->verify(
    Receipt::fromPath('contract.pdf.ots'),
    FileToStamp::fromPath('contract.pdf'),   // optional: also check it is *this* file's proof
);                                           // or FileToStamp::fromDigest(hex2bin($hex)), in the proof's algorithm

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

**One good attestation is enough.** A proof usually holds one branch per
calendar, and they are independent: a branch whose block does not commit to
the proof does not undo one that does. So a mismatch only makes the verdict
`Failed` when no other attestation is verified (a file mismatch always does).
Mismatches stay visible whatever the verdict, and are worth surfacing: one
of the calendars handed out something wrong.

```php
if ($report->isVerified() && $report->mismatches() !== []) {
    foreach ($report->mismatches() as $verification) {
        echo 'Ignored: block ', $verification->blockHeight(), ' does not commit to the proof', "\n";
    }
}
```

`Verifier::checkAnchors()` runs the same check on any list of `BitcoinAnchor`,
fetching the chain tip once; it is what `upgrade()` uses on calendar answers.

### Choosing where block headers come from

Without configuration, block headers come from **mempool.space**
(`Explorer::DEFAULT`). Any `BlockHeaderSource` can replace it; the interface
is neutral, so the same verifier can be backed by another public explorer, a
self-hosted one, your own Bitcoin node, several at once, or a fake:

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
several explorers vouch for the same header. Or trust nobody else and ask
your own node, below.

Explorer traffic is hardened like calendar traffic: https only, redirects
never followed, tiny response cap, timeouts on idle and total duration.

### Verifying against your own Bitcoin node

`BitcoinRpcBlockHeaderSource` talks to any node speaking the Bitcoin Core
JSON-RPC protocol (`getblockhash`, `getblockheader`, `getblockcount`). Only
block headers are asked for, so a **pruned node is enough**, and no wallet
or transaction index is needed. Hosted RPC providers speaking the same
protocol work too.

```php
use CondorcetVote\ElephStamp\Verify\BitcoinRpcBlockHeaderSource;

// Credentials in the URL…
$node = new BitcoinRpcBlockHeaderSource('http://user:password@127.0.0.1:8332');

// …or as arguments…
$node = new BitcoinRpcBlockHeaderSource('http://127.0.0.1:8332', user: 'user', password: 'password');

// …or from the .cookie file Bitcoin Core writes when rpcpassword is not set.
$node = new BitcoinRpcBlockHeaderSource('http://127.0.0.1:8332', cookieFile: '/home/bitcoin/.bitcoin/.cookie');

// A hosted provider: a node you did not run, so its operator is trusted like an explorer.
$node = new BitcoinRpcBlockHeaderSource('https://rpc.example.com/v1/your-token');

$client = new ElephStamp(blockHeaderSource: $node);

// Your node and a public explorer must agree.
$client = new ElephStamp(blockHeaderSource: new CrossCheckingBlockHeaderSource($node, Explorer::MempoolSpace->source()));
```

Optional arguments mirror the explorer driver: an injectable
`HttpClientInterface`, `userAgent`, `timeout` (idle) and `maxDuration`
(total) in seconds, and a `label` for `describe()` (the host and port by
default).

Give the credentials one way only; the constructor throws
`InvalidInputException` when they come from several places, when a user has
no password (or the reverse), or when the cookie file cannot be read. They
never appear in `describe()` or in exception messages.

Plain `http` is accepted only for **local and private hosts**: `localhost`,
a hostname without a dot (a Docker service name, say), loopback, RFC 1918
and link-local addresses. Any other host must be reached over `https`, or
the constructor throws `InvalidInputException`. Redirects are never followed
and answers are size-capped, as for explorers. The node's raw header goes
through the same local proof-of-work check as an explorer's.

A refused login (HTTP 401/403), an RPC error (a height past the tip is
reported as an unknown block, like an explorer's 404) or a node still
loading its block index all surface as `BlockSourceException`, which
`verify()` turns into an unavailable attestation.

### Verifying in fake mode

`ElephStamp::fake()` comes with a `FakeBlockHeaderSource`, and the fake
calendar **mines its confirmations into it**: confirming registers a block
whose merkle root is the one the confirmed proofs lead to. So a confirmed
receipt upgrades (with verification) and verifies without further setup:

```php
$client = ElephStamp::fake();

$receipt = $client->stamp(FileToStamp::fromContent('hello'));
$client->fakeCalendar()->confirmAll(812_345, new DateTimeImmutable('2024-06-01 12:00 UTC'));
$client->upgrade($receipt);             // verified against the fake chain, merged

$client->verify($receipt)->verdict();   // Verdict::Verified
$client->verify($receipt)->attestedAt(); // 2024-06-01 12:00 UTC

// Simulate a chain that has not grown yet: the upgrade holds the answer back.
$client->fakeBlockSource()->setTipHeight(812_346);  // upgrade → Unconfirmed, verify → AwaitingConfirmations

// Simulate a calendar that lies: rewrite the chain behind its back.
$blocks = $client->fakeBlockSource();
$blocks->reset();
$blocks->addBlock(812_345, $otherRoot);             // upgrade → Rejected, verify → Failed
$blocks->reset();                                   // upgrade → Unverifiable, verify → Inconclusive
```

`FakeBlockHeaderSource::anchor($receipt)` still registers, after the fact,
the blocks a receipt already carries (useful for a receipt loaded from disk),
and `addBlock()` refuses to give a height a second, different merkle root.

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
$calendar->confirmAll();       // confirm every commitment submitted so far and not confirmed yet
```

Each call mines **one block**: the commitments it confirms are bound in a
merkle tree, like a real calendar aggregating its clients, and the block is
registered in the calendar's `blocks()` (the `FakeBlockHeaderSource` the
client verifies against). Without an explicit height the first block is
`FakeCalendarClient::DEFAULT_BLOCK_HEIGHT` (800000) and the next ones follow
it, so repeated `confirmAll()` calls just grow the chain. A block is immutable
once mined: confirming other commitments at a height already used throws an
`InvalidInputException`, and an already confirmed commitment stays in its
block. `reset()` forgets submissions, confirmations and the mined blocks.

To share a chain between a calendar and a client, build the calendar with it:
`new FakeCalendarClient(blocks: $source)`; `ElephStamp::fake($calendar, $source)`
refuses a pair that do not match.

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

The other constructor arguments each have their own section: `hashOperation`
([Hash algorithm](#hash-algorithm)), `upgradeWhitelist`
([Upgrade whitelist](#upgrade-whitelist-security)), `blockHeaderSource`
([Choosing where block headers come from](#choosing-where-block-headers-come-from)),
`calendarClient` ([Customising the HTTP client](#customising-the-http-client))
and `randomSource` (the nonce generator, deterministic in [fake mode](#testing-fake-mode)).

### Upgrade whitelist (security)

An `.ots` proof embeds the calendar URIs to poll when upgrading. Because a proof
may come from an untrusted source, `upgrade()` only contacts hosts on an
allowlist — otherwise a hostile proof could point the process at arbitrary hosts
(an SSRF risk). The default covers the known public operators
(`*.calendar.opentimestamps.org`, `*.calendar.eternitywall.com`,
`*.calendar.catallaxy.com`).

The calendars in `calendarUrls` are always allowed on top of the whitelist:
you already trust them with your stamps, so a private calendar is upgradable
without being declared twice:

```php
$client = new ElephStamp(calendarUrls: ['https://ots.internal.example']);
```

Pass `upgradeWhitelist` to replace the public-operator patterns, for instance
to upgrade proofs stamped through other calendars you trust, or `[]` to allow
only your own calendars:

```php
$client = new ElephStamp(
    calendarUrls: ['https://ots.internal.example'],
    upgradeWhitelist: ['https://*.partner.example'],
);
```

Host patterns accept shell-style globs, but a glob must be followed by at
least two literal domain labels, the last one not numeric:
`https://*.internal.example` is fine, while `https://*`, `https://*.example`
or `https://10.0.0.*` throw an `InvalidInputException`, since they would let a
hostile proof reach any host, a whole top-level domain or a private network.
A pattern without a glob is an explicit choice and may name any host,
including an IP address (`https://10.0.0.5`). URLs with a query, fragment or
credentials are always rejected.

Patterns and proof URIs are parsed strictly as RFC 3986 (with PHP's native
`Uri\Rfc3986\Uri`) and compared in normalized form: lowercase host, no dot
segments, `:443` the same as no port. Anything the parser cannot read, such as
a backslash or a non-ASCII host, is rejected. The request then goes to that
normalized URL, never to the raw string from the proof, so what the whitelist
checked is exactly what is contacted. `CalendarWhitelist::resolve()` exposes
this for your own checks:

```php
use CondorcetVote\ElephStamp\Calendar\CalendarWhitelist;

$whitelist = new CalendarWhitelist(['https://*.calendar.opentimestamps.org']);

$whitelist->resolve('HTTPS://Alice.BTC.Calendar.OpenTimestamps.org:443/'); // 'https://alice.btc.calendar.opentimestamps.org'
$whitelist->resolve('https://evil.example\\@alice.btc.calendar.opentimestamps.org'); // null
$whitelist->allows('https://alice.btc.calendar.opentimestamps.org'); // true
```

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
