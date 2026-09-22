# AGENTS.md

Authoritative instructions for AI coding agents working on this repository.

## Project

ElephStamp is a modern, object-oriented PHP library for
[OpenTimestamps](https://opentimestamps.org/). It is a focused, partial port of
the Python `opentimestamps-client` (source of truth for the format:
`/Users/julien/Code/opentimestamps-client` and its `python-opentimestamps`
dependency) — not a line-by-line translation, but an expressive PHP API.

Its job is narrow: **submit timestamp requests to calendar servers** and
**track/collect the completed `.ots` proof**. The `.ots` files it reads and
writes are byte-for-byte interoperable with the reference tooling (validated by
round-trip tests against real fixtures).

## Language & tooling

- Modern PHP — minimum PHP **8.5** (see `composer.json`).
- Every symbol must be strictly typed (parameters, return types, properties).
- PHPStan must pass (`vendor/bin/phpstan analyse`).
- PHPDoc on every public symbol. Use PHPDoc for shapes PHP cannot express (`list<string>`, `array<string, int>`, etc.).
- All identifiers, comments, docs, commit messages, exceptions: **English only**.
- Comments only when the *why* is non-obvious. Code should be self-documenting.
- **Never use `assert()`** (nor the `\assert()` language construct). Assertions
  can be disabled in production (`zend.assertions`), so they are not a reliable
  guard. Use normal runtime checks that always execute and throw an appropriate
  exception instead.
- Follow `.php-cs-fixer.dist.php` (run `vendor/bin/php-cs-fixer fix`).

## Testing

- Use **Pest 5** for everything. Tests live under `tests/`.
- Aim for thorough coverage: every public method, every exception path, every format option.
- Run with `vendor/bin/pest`.

## Documentation

Hand-written documentation is split in four files at the repository root.
**Always keep them up to date**, in the same change set as the code:

- `README.md` — short entry point: pitch, scope, requirements, installation,
  a thirty-second example, and links to the two guides. Keep it short; details
  belong in the guides.
- `LIBRARY.md` — the PHP API guide. Every change to a public API surface (new
  method, new class, renamed method, changed signature, new exception type,
  new runtime requirement, new behaviour) MUST be reflected here.
- `CLI.md` — the `elephstamp` command guide. Every new command, option, output
  change, JSON field or exit code MUST be reflected here, including the
  sample outputs when they no longer match.
- `CHANGELOG.md` — the release history, newest first, in the Keep a Changelog
  style. Anything a consumer of the library or the CLI would notice goes under
  the unreleased version at the top (`### Added` / `### Changed` / `### Fixed`
  / `### Removed`), in the same change set as the code. Written for users, not
  for maintainers: skip lint, internal refactors and test-only changes. Never
  date or renumber a version, and never edit an already released section — the
  maintainer cuts releases.

Treat these files as part of the API: out-of-date docs are a bug. Public
examples must actually run as written — when in doubt, copy them into a
scratch script and verify before committing.

- `docs/` holds the **generated** class reference (`composer document`). The
  generator wipes that directory on every run, so never put hand-written
  documentation there.
- Do **not** put project instructions inside `.github/` — they belong in this file.
- The generated API docs and all git commits are handled by the maintainer. Do
  **not** run `composer document`, do **not** commit, tag, or push. Keep the
  four documentation files current, but leave `docs/` and version control to
  the maintainer.

## Scope of this library

**In scope**

- Creating timestamp requests and submitting digests to calendar servers.
- Following a proof's status and upgrading a pending `.ots` to a completed one
  once a calendar can return a blockchain attestation.
- A first-class **fake mode** (`ElephStamp::fake()` / `FakeCalendarClient`) for
  consumers' test suites and local integration environments: deterministic,
  network-free, with an explicit pending → confirmed lifecycle.
- The `elephstamp` **command-line tool** (`bin/elephstamp`, Symfony Console),
  exposing the library's features with readable, per-calendar reports. It is
  a thin presentation layer: anything a command needs to know must be
  available through the library's public API (add it there first).

- Verifying a complete proof against the Bitcoin blockchain through a
  `Verify\BlockHeaderSource`. The interface is neutral (explorer, node, fake).
  Two drivers exist: Esplora (`EsploraBlockHeaderSource`, behind the
  `Explorer` enum for mempool.space and blockstream.info) and Bitcoin Core
  JSON-RPC (`BitcoinRpcBlockHeaderSource`, for a node you run or a hosted RPC
  provider; CLI `--node`). Never hard-code an explorer's or node's API in the
  verifier: add a driver. The RPC driver accepts plain `http` only for local
  and private hosts and never lets credentials into messages; keep both.

**Out of scope (for now)**

- Non-Bitcoin attestations (Litecoin, Ethereum/Keccak-256) are recognised
  only enough to be preserved or clearly rejected, not verified.
- Reading `bitcoin.conf` for RPC credentials: the CLI takes them explicitly
  (`--node-user`/`--node-password`, `--node-cookie`, or in the URL).

## Architecture notes

- Wire format lives under `Serialization/`, `Operation/`, `Attestation/`,
  `Timestamp`, `DetachedTimestampFile`, `Merkle/`. These are consensus-critical:
  keep them byte-compatible with the reference format.
- Network access goes through the `Calendar\CalendarClient` seam, which is a
  **batch** API: the facade hands over every calendar at once and gets one
  `CalendarResponse` per request back (success / not-found / failure). The real
  implementation (`HttpCalendarClient`) uses the Symfony HTTP client directly
  and dispatches the batch **concurrently** (a custom `HttpClientInterface` is
  injectable — e.g. `MockHttpClient` in tests); the fake serves from memory. No
  PSR HTTP abstractions are used. A single calendar's failure is captured in its
  `CalendarResponse`, never thrown, so it can never abort a stamp or upgrade —
  the m-of-n threshold is enforced by the facade.
- Randomness goes through `Random\RandomSource` so the fake/tests are
  deterministic. Always use PHP's `Random\Randomizer` API, never legacy RNG.
- `upgrade()` only contacts calendar URIs allowed by a `Calendar\CalendarWhitelist`
  (default: the known public operators). An `.ots` is untrusted input; contacting
  its embedded URIs unfiltered would be an SSRF vector. Keep this guard.
- `.ots` files produced/consumed must stay interoperable; the fixtures in
  `tests/fixtures/` are genuine reference-client output and must round-trip
  byte-for-byte.
- `upgrade()` is a thin wrapper over `upgradeWithReport()`, which returns an
  `Upgrade\UpgradeReport` describing what every calendar answered. Keep the
  two in sync: any change to the polling logic goes in `upgradeWithReport()`.
- Verification recomputes everything locally and asks the source only for
  block headers. A source returning the raw 80-byte header goes through
  `BlockHeader::fromRawHeader()`, which recomputes the hash and checks the
  proof of work; keep that check, it is what limits the trust put in an
  explorer. Explorer answers are untrusted input: https only, no redirects,
  size caps, strict parsing.
- `--json` is a machine contract: the document must be identical at every
  verbosity, and every hash in it complete. `Console\Formatter::hex()` /
  `abbreviate()` belong to the human rendering only — never call them from a
  command's `toArray()`, use the full `…Hex()` accessors. Guarded by
  `tests/Feature/Console/JsonHashesTest.php`.
- The CLI lives under `Console/`. Commands are Symfony invokable commands
  (`#[AsCommand]` + `#[Argument]`/`#[Option]` attributes). They obtain their
  `ElephStamp` through the `Console\ClientFactory` seam so tests can run them
  against a fake calendar (`tests/Feature/Console/FakeClientFactory.php`);
  never instantiate `HttpCalendarClient` inside a command. Proof analysis for
  display lives in `Console\Inspection\ProofInspector`, built only from the
  public API. Exit codes are part of the CLI contract and documented in each
  command's help text; `upgrade` returns `2` when a proof is still pending.
- Test the CLI through `Symfony\Component\Console\Tester\ApplicationTester`
  with `Console\Application`, and prefer asserting on `unwrapped()` output
  when a long path may be line-wrapped by SymfonyStyle.

