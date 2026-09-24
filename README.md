# ElephStamp

[![Packagist Version](https://img.shields.io/packagist/v/condorcet-vote/elephstamp)](https://packagist.org/packages/condorcet-vote/elephstamp)
[![Packagist Downloads](https://img.shields.io/packagist/dt/condorcet-vote/elephstamp)](https://packagist.org/packages/condorcet-vote/elephstamp)
[![CI](https://github.com/CondorcetVote/Elephstamp/actions/workflows/ci.yml/badge.svg)](https://github.com/CondorcetVote/Elephstamp/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/condorcet-vote/elephstamp)](LICENSE)

**Prove that a file existed at a given time, anchored in the Bitcoin
blockchain.** ElephStamp is a PHP library and a command-line tool for
[OpenTimestamps](https://opentimestamps.org/): stamp a file with the public
calendar servers, collect the completed proof a few hours later, and verify
it against the blockchain.

Its `.ots` proofs are byte-for-byte interoperable with the reference
[`opentimestamps-client`](https://github.com/opentimestamps/opentimestamps-client)
(`ots`), but on what it covers it goes further:

- **Safer.** Every answer a calendar sends back is checked against the
  blockchain before it is merged into your proof.
- **More informative.** Readable reports say what every calendar did, which
  block and transaction carry your proof, and why a proof is still pending.
- **Friendlier.** Verify without running a Bitcoin node, stamp many files in
  one go, script it with documented exit codes and `--json`.
- **A real library.** A typed, object-oriented PHP API for the whole
  lifecycle, with a built-in fake mode for your own test suite.

See [how it differs from the reference client](#differences-from-the-reference-client)
for the details.

## Contents

- [Documentation](#documentation)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick tour: command line](#quick-tour-command-line)
- [Quick tour: PHP library](#quick-tour-php-library)
- [Scope](#scope)
- [Differences from the reference client](#differences-from-the-reference-client)
- [License](#license)

## Documentation

| | |
| --- | --- |
| **[CLI-INSTALL.md](CLI-INSTALL.md)** | Installing the `elephstamp` command: PHAR, Docker or Composer. |
| **[CLI.md](CLI.md)** | Using the `elephstamp` command: `stamp`, `upgrade`, `verify`, `info`, `tree`, `calendars`, JSON output, exit codes. |
| **[LIBRARY.md](LIBRARY.md)** | Using the PHP API: stamping, upgrading, verifying, reading receipts, fake mode for tests, configuration, security. |
| [docs/readme.md](docs/readme.md) | Generated class-by-class API reference. |
| [CHANGELOG.md](CHANGELOG.md) | What changed in each release. |

## Requirements

- PHP **8.5+** with the `mbstring` extension (not needed with the Docker
  image).
- The `curl` extension is recommended: calendars, block explorers and nodes
  are then contacted through curl, faster and over HTTP/2. Without it,
  everything still works, through PHP streams. The Docker image has it.
- For the library, Composer pulls in the rest:
  [`symfony/http-client`](https://symfony.com/doc/current/http_client.html),
  [`symfony/console`](https://symfony.com/doc/current/components/console.html)
  and [`kornrunner/keccak`](https://github.com/kornrunner/php-keccak).

## Installation

### The PHP library

```bash
composer require condorcet-vote/elephstamp
```

That is all. The `elephstamp` command comes along, in `vendor/bin/`.

### The command-line tool

Three ways, detailed in [CLI-INSTALL.md](CLI-INSTALL.md):

- **Standalone PHAR**, attached to every
  [GitHub release](https://github.com/CondorcetVote/Elephstamp/releases/latest),
  with a checksum and a signed build provenance attestation.
- **Docker image** [`julienboudry/elephstamp`](https://hub.docker.com/r/julienboudry/elephstamp),
  when PHP 8.5 is not at hand.
- **Composer**, as a global tool: `composer global require condorcet-vote/elephstamp`.

## Quick tour: command line

### Stamp a file

```bash
elephstamp stamp contract.pdf
```

```
  File           sha256 digest       Proof written to
  contract.pdf   357ebe89…b412c788   contract.pdf.ots

 Accepted by 4 calendars:
 * https://alice.btc.calendar.opentimestamps.org
 * https://bob.btc.calendar.opentimestamps.org
 * https://finney.calendar.eternitywall.com
 * https://btc.calendar.catallaxy.com

 [OK] 1 proof written. Status: pending — Bitcoin confirmation usually takes a few hours.
```

The proof is **pending**: the calendars have recorded it, Bitcoin has not
confirmed it yet. `elephstamp stamp *.pdf` stamps many files in one
submission, one proof each.

### Collect the Bitcoin confirmation

A few hours later:

```bash
elephstamp upgrade contract.pdf.ots
```

```
  Calendar                                        Commitment          Answer
  https://alice.btc.calendar.opentimestamps.org   6aada194…779bcb5c   upgraded — Bitcoin block 912345, verified (14 confirmations)
  https://bob.btc.calendar.opentimestamps.org     6aada194…9b8504a8   still pending — not confirmed yet
  https://finney.calendar.eternitywall.com        6aada193…4fc5e50b   failed — Calendar https://finney…: Idle timeout reached

 Answers checked against the blockchain through mempool.space before being merged.

 [OK] Now complete: anchored in Bitcoin block 912345. Saved to contract.pdf.ots.
```

It exits `2` while the proof is still pending, so a cron job can simply
retry until it succeeds.

### Verify it against the blockchain

```bash
elephstamp verify contract.pdf.ots
```

```
 VERIFIED

 contract.pdf existed before 2026-09-18 15:27:58 UTC (Bitcoin block 967571).

  Original         contract.pdf — digest matches the proof
  Proof            sha256 digest 065470e2…, 3 Bitcoin attestations recomputed offline
  Block headers    mempool.space — trusted for block headers only
  Required depth   6 confirmations
```

The block headers come from mempool.space by default; `--explorer
blockstream`, `--explorer-url` or `--node` (your own Bitcoin node) change that.

### Understand a proof

```bash
elephstamp info contract.pdf.ots     # status, calendars, block, transaction id: offline
elephstamp tree contract.pdf.ots     # every operation and every hash, to check by hand
```

```
  Status             pending — waiting on 2 calendars; run "upgrade" to poll
  File hash          sha256
  File digest        d32fee9a827f5a0d580f80beb7edce662dd99fcd6591e4ef8a6244403df0b7c9
  Submitted digest   c8f78972680bec45199bf6f19472fe1db32ceeed4509c79f345086bf4888d3fa — root of a batch merkle tree, behind a privacy nonce
  Original file      merkle1.txt — digest matches
  Proof size         335 bytes

  Calendar                                        Recorded at (calendar clock, UTC)   Commitment          State
  https://alice.btc.calendar.opentimestamps.org   2016-09-14 17:03:27                 57d982df…55590477   pending, upgradable
  https://bob.btc.calendar.opentimestamps.org     2016-09-14 17:03:28                 57d982e0…3071762a   pending, upgradable
```

Every command reading proofs takes several files and `--json`; see
[CLI.md](CLI.md) for all of it.

## Quick tour: PHP library

### Stamp, upgrade, verify

```php
use CondorcetVote\ElephStamp\ElephStamp;
use CondorcetVote\ElephStamp\FileToStamp;
use CondorcetVote\ElephStamp\Receipt;

$client = new ElephStamp();

// Today: register the proof. It is "pending" until Bitcoin confirms it.
$receipt = $client->stamp(FileToStamp::fromPath('contract.pdf'));
$receipt->saveToPath('contract.pdf.ots');

// A few hours later: collect the completed proof. Each calendar's answer is
// verified against the blockchain before it is merged.
$receipt = Receipt::fromPath('contract.pdf.ots');

if ($client->upgrade($receipt)) {
    $receipt->save();   // back to contract.pdf.ots
}

if ($receipt->isComplete()) {
    echo 'Anchored in Bitcoin block ' . $receipt->bitcoinBlockHeight();

    // Check it against the chain, through a public block explorer (or your node, see LIBRARY.md).
    $report = $client->verify($receipt, FileToStamp::fromPath('contract.pdf'));
    echo $report->verdict()->name;   // Verified
}
```

### What each calendar answered

```php
$report = $client->upgradeWithReport($receipt);

foreach ($report->results as $result) {
    echo $result->calendarUrl, ': ', $result->outcome->name, "\n";   // Upgraded, Pending, Failed…
}

if ($report->changed()) {
    $receipt->save();
}
```

### In your tests: fake mode

```php
$client = ElephStamp::fake();   // no network, deterministic

$receipt = $client->stamp(FileToStamp::fromContent('hello world'));
$client->fakeCalendar()->confirmAll(blockHeight: 812_345);   // "Bitcoin" confirms it

$client->upgrade($receipt);
$client->verify($receipt)->verdict();   // Verdict::Verified
```

Continue with [LIBRARY.md](LIBRARY.md).

## Scope

### In scope

- Creating timestamp requests and submitting them to calendar servers.
- Following a proof's status and fetching the completed `.ots` once the
  calendars can provide a blockchain attestation, **checked against the
  blockchain before it is accepted** into the proof.
- Verifying a completed proof against the Bitcoin blockchain, through a public
  block explorer (mempool.space by default, blockstream.info or any Esplora
  instance on request) or **your own Bitcoin node** over JSON-RPC, a pruned
  one included. The block header's proof of work is checked locally.
- An `elephstamp` **command-line tool** exposing all of the above, with
  readable reports on what every calendar is up to.
- A built-in **fake mode** for tests and local/integration environments.

### Out of scope (for now)

- Non-Bitcoin attestations (Litecoin, Ethereum): preserved, never interpreted.
- Git integration, pruning proofs, running a calendar server.

## Differences from the reference client

The wire format is the reference's: an `.ots` stamped here upgrades and
verifies with `ots`, and the other way round. What ElephStamp *does* with
proofs departs from the Python client on purpose.

### Safer

- **Upgrades are verified before they are merged.** `ots upgrade` merges
  whatever a calendar returns; ElephStamp checks every Bitcoin attestation in
  an answer against the blockchain first (merkle root of the named block,
  and enough confirmations). A wrong or hostile answer is discarded and the
  calendar stays pending, instead of leaving you with a "complete" proof that
  fails verification. Opt out with `verify: false` / `--no-verify`.
- **One verified attestation is enough.** A proof carries one branch per
  calendar; `verify()` passes when one of them is confirmed by its block,
  and reports the branches that do not match alongside rather than letting
  one bad calendar fail the whole proof. The file digest, of course, has to
  match.
- **Hostile proofs are contained.** An `.ots` is untrusted input: its
  calendar URIs are only contacted when they match a whitelist, and parsing is
  bounded.

### More informative

- **Every calendar is reported on.** Calendars are contacted concurrently;
  each one's outcome (upgraded, pending, failed, rejected, unconfirmed,
  unverifiable, skipped) is reported per calendar, with the reason. A
  calendar failure never aborts a run: the m-of-n threshold on stamping is
  the only thing that can fail a `stamp`.
- **Proofs are explained, not just dumped.** `info` gives the status, what
  the calendars actually received, the block, the transaction id and a link
  to look it up; `tree` annotates the raw tree with where your side ends and
  the calendars take over.
- **Every calendar's attestation can be collected.** Like `ots upgrade`,
  an upgrade stops at the first Bitcoin attestation. ElephStamp adds
  `pollAll` / `--all` to keep polling the other calendars and gather every
  branch into the proof.

### Friendlier

- **No Bitcoin node required.** `ots verify` needs a Bitcoin Core RPC;
  ElephStamp asks mempool.space (or blockstream.info, or any Esplora
  instance, several of them cross-checked) for the raw block header and
  checks its proof of work locally, so the explorer is trusted only for the
  header's existence. Your own node is supported too (`--node`), pruned or
  not, alone or cross-checked with the explorers.
- **Made for scripts.** Documented exit codes (`2` means "not yet"), a stable
  `--json` document for `info`, `upgrade` and `verify`, several proofs per
  command.
- **Easy to install.** A PHAR, a Docker image or Composer.

### A real library

- **The whole lifecycle as a PHP API.** In the reference project, stamping
  and upgrading live in the `ots` command; ElephStamp exposes them as typed
  objects (`ElephStamp`, `Receipt`, `UpgradeReport`, `VerificationReport`)
  you can build on, with pluggable calendar clients and block header sources.
- **Fake mode is built in.** `ElephStamp::fake()` gives a deterministic,
  network-free calendar and chain for your own test suite, with an explicit
  pending → confirmed lifecycle.

### Narrower scope

No Git integration, no Litecoin or Ethereum verification (such attestations
are preserved untouched), no calendar server: stamping, upgrading and
verifying against Bitcoin only.

## License

MIT — see [LICENSE](LICENSE).
