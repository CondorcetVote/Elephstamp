# ElephStamp

[![Packagist Version](https://img.shields.io/packagist/v/condorcet-vote/elephstamp)](https://packagist.org/packages/condorcet-vote/elephstamp)
[![Packagist Downloads](https://img.shields.io/packagist/dt/condorcet-vote/elephstamp)](https://packagist.org/packages/condorcet-vote/elephstamp)
[![CI](https://github.com/CondorcetVote/Elephstamp/actions/workflows/ci.yml/badge.svg)](https://github.com/CondorcetVote/Elephstamp/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/condorcet-vote/elephstamp)](LICENSE)

A modern, object-oriented PHP library for [OpenTimestamps](https://opentimestamps.org/),
with a friendly command-line tool. It lets you **register a timestamp proof**
for a file with the public calendar servers and **track/collect the completed
proof** once it has been anchored in the Bitcoin blockchain.

ElephStamp is a focused, partial port of the Python
[`opentimestamps-client`](https://github.com/opentimestamps/opentimestamps-client):
it is not a line-by-line translation but an expressive PHP API. The `.ots`
proof files it produces and reads are byte-for-byte interoperable with the
reference tooling; see [how it differs](#differences-from-the-reference-client)
in what it does with them.

## Documentation

| | |
| --- | --- |
| **[LIBRARY.md](LIBRARY.md)** | The PHP API: stamping, upgrading, verifying, reading receipts, fake mode for tests, configuration, security. |
| **[CLI.md](CLI.md)** | The `elephstamp` command: `stamp`, `upgrade`, `verify`, `info`, `tree`, `calendars`, JSON output, exit codes. |
| [docs/readme.md](docs/readme.md) | Generated class-by-class API reference. |

## Scope

**In scope**

- Creating timestamp requests and submitting them to calendar servers.
- Following a proof's status and fetching the completed `.ots` once the
  calendars can provide a blockchain attestation, **checked against the
  blockchain before it is accepted** into the proof.
- Verifying a completed proof against the Bitcoin blockchain, through a public
  block explorer: mempool.space by default, blockstream.info or any Esplora
  instance on request. The block header's proof of work is checked locally.
- An `elephstamp` **command-line tool** exposing all of the above, with
  readable reports on what every calendar is up to.
- A built-in **fake mode** for tests and local/integration environments.

**Out of scope (for now)**

- Verifying against a Bitcoin node you run. The block-header abstraction is
  ready for it, but only explorers are implemented; an explorer is a third
  party you trust for block headers.
- Non-Bitcoin attestations (Litecoin, Ethereum): preserved, never interpreted.

## Requirements

- PHP **8.5+** with the `mbstring` extension.
- [`symfony/http-client`](https://symfony.com/doc/current/http_client.html)
  and [`symfony/console`](https://symfony.com/doc/current/components/console.html)
  (pulled in automatically).

## Installation

```bash
composer require condorcet-vote/elephstamp
```

The `elephstamp` command lands in `vendor/bin/`. To use it as a standalone
tool, install it globally instead:

```bash
composer global require condorcet-vote/elephstamp
```

## In thirty seconds

From PHP:

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

    // Check it against the chain, through a public block explorer.
    $report = $client->verify($receipt, FileToStamp::fromPath('contract.pdf'));
    echo $report->verdict()->name;   // Verified
}
```

From the shell:

```bash
elephstamp stamp contract.pdf         # → contract.pdf.ots
elephstamp upgrade contract.pdf.ots   # later: fetch the Bitcoin attestation
elephstamp verify contract.pdf.ots    # check it against the blockchain via a block explorer
elephstamp info contract.pdf.ots      # what each calendar did, block height, digest check
```

Continue with [LIBRARY.md](LIBRARY.md) or [CLI.md](CLI.md).

## Differences from the reference client

The wire format is the reference's: an `.ots` stamped here upgrades and
verifies with `ots`, and the other way round. What ElephStamp *does* with
proofs departs from the Python client on purpose, in a few places:

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
- **Block headers come from a block explorer, not a Bitcoin node.** `ots
  verify` needs a Bitcoin Core RPC; ElephStamp asks mempool.space (or
  blockstream.info, or any Esplora instance, several of them cross-checked)
  for the raw header and checks its proof of work locally, so the explorer
  is trusted only for the header's existence. Verifying against your own node
  is planned, not implemented.
- **Every calendar's attestation can be collected.** Like `ots upgrade`,
  an upgrade stops at the first Bitcoin attestation: once a proof is
  complete, the calendars still pending in it are left alone. ElephStamp adds
  `pollAll` / `--all` to keep polling them and gather every calendar's
  branch into the proof.
- **Calendar failures never abort a run.** Calendars are contacted
  concurrently; each one's outcome (upgraded, pending, failed, rejected,
  unconfirmed, unverifiable, skipped) is reported per calendar, and the
  m-of-n threshold on stamping is the only thing that can fail a `stamp`.
- **Fake mode is built in.** `ElephStamp::fake()` gives a deterministic,
  network-free calendar and chain for your own test suite, with an explicit
  pending → confirmed lifecycle.
- **Narrower scope.** No Git integration, no Litecoin or Ethereum
  verification (such attestations are preserved untouched), no calendar
  server: stamping, upgrading and verifying against Bitcoin only.

## License

MIT — see [LICENSE](LICENSE).
