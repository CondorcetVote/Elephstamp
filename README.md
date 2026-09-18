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
reference tooling.

## Documentation

| | |
| --- | --- |
| **[LIBRARY.md](LIBRARY.md)** | The PHP API: stamping, upgrading, reading receipts, fake mode for tests, configuration, security. |
| **[CLI.md](CLI.md)** | The `elephstamp` command: `stamp`, `upgrade`, `info`, `tree`, `calendars`, JSON output, exit codes. |
| [docs/readme.md](docs/readme.md) | Generated class-by-class API reference. |

## Scope

**In scope**

- Creating timestamp requests and submitting them to calendar servers.
- Following a proof's status and fetching the completed `.ots` once the
  calendars can provide a blockchain attestation.
- An `elephstamp` **command-line tool** exposing all of the above, with
  readable reports on what every calendar is up to.
- A built-in **fake mode** for tests and local/integration environments.

**Out of scope (by design)**

- No on-chain verification. ElephStamp reports what a proof *claims* (e.g. the
  Bitcoin block height) but never talks to a Bitcoin node to verify it. Use a
  dedicated verifier for that.

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

// A few hours later: collect the completed proof.
$receipt = Receipt::fromPath('contract.pdf.ots');

if ($client->upgrade($receipt)) {
    $receipt->saveToPath('contract.pdf.ots');
}

if ($receipt->isComplete()) {
    echo 'Anchored in Bitcoin block ' . $receipt->bitcoinBlockHeight();
}
```

From the shell:

```bash
elephstamp stamp contract.pdf         # → contract.pdf.ots
elephstamp upgrade contract.pdf.ots   # later: fetch the Bitcoin attestation
elephstamp info contract.pdf.ots      # what each calendar did, block height, digest check
```

Continue with [LIBRARY.md](LIBRARY.md) or [CLI.md](CLI.md).

## License

MIT — see [LICENSE](LICENSE).
