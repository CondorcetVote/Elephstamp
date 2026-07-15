# ElephStamp

A modern, object-oriented PHP library for [OpenTimestamps](https://opentimestamps.org/).
It lets you **register a timestamp proof** for a file with the public calendar
servers and **track/collect the completed proof** once it has been anchored in
the Bitcoin blockchain.

ElephStamp is a focused, partial port of the Python
[`opentimestamps-client`](https://github.com/opentimestamps/opentimestamps-client):
it is not a line-by-line translation but an expressive PHP API. The `.ots`
proof files it produces and reads are byte-for-byte interoperable with the
reference tooling.

## Scope

**In scope**

- Creating timestamp requests and submitting them to calendar servers.
- Following a proof's status and fetching the completed `.ots` once the
  calendars can provide a blockchain attestation.
- A built-in **fake mode** for tests and local/integration environments.

**Out of scope (by design)**

- No command-line tool — this is a library only.
- No on-chain verification. ElephStamp reports what a proof *claims* (e.g. the
  Bitcoin block height) but never talks to a Bitcoin node to verify it. Use a
  dedicated verifier for that.

## Requirements

- PHP **8.5+** with the `mbstring` extension.
- [`symfony/http-client`](https://symfony.com/doc/current/http_client.html)
  (pulled in automatically) for talking to calendar servers.

## Installation

```bash
composer require condorcet-vote/elephstamp
```

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

## Reading a receipt

```php
$receipt->status();               // Status::Pending | Status::Complete
$receipt->isComplete();
$receipt->isPending();
$receipt->fileDigest();           // raw digest bytes
$receipt->fileDigestHex();        // lower-case hex
$receipt->pendingCalendarUris();  // list<string> of calendars still to poll
$receipt->bitcoinBlockHeight();   // int|null (claimed, not verified)
$receipt->toBytes();              // the raw .ots content
$receipt->describe();             // human-readable proof tree (for inspection)
```

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
patterns.

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

## License

MIT — see [LICENSE](LICENSE).
