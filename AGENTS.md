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

- Use **Pest 4** for everything. Tests live under `tests/`.
- Aim for thorough coverage: every public method, every exception path, every format option.
- Run with `vendor/bin/pest`.

## Documentation

- `README.md` is the project's public documentation. **Always keep it up to date.**
  Every change that adds, removes, or modifies a public API surface (new method,
  new class, renamed method, changed signature, new exception type, new
  runtime requirement, new behaviour) MUST be reflected in `README.md` in the
  same change set. Treat the README as part of the API: out-of-date docs are
  a bug.
- Public examples in the README must actually run as written — when in doubt,
  copy them into a scratch script and verify before committing.
- Do **not** put project instructions inside `.github/` — they belong in this file.
- The generated API docs (`composer document`, output in `docs/`) and all git
  commits are handled by the maintainer. Do **not** run `composer document`, do
  **not** commit, tag, or push. Keep `README.md` current, but leave `docs/` and
  version control to the maintainer.

## Scope of this library

**In scope**

- Creating timestamp requests and submitting digests to calendar servers.
- Following a proof's status and upgrading a pending `.ots` to a completed one
  once a calendar can return a blockchain attestation.
- A first-class **fake mode** (`ElephStamp::fake()` / `FakeCalendarClient`) for
  consumers' test suites and local integration environments: deterministic,
  network-free, with an explicit pending → confirmed lifecycle.

**Out of scope (deliberately)**

- No command-line interface — library only.
- No on-chain verification. The library reports what a proof *claims* (e.g.
  Bitcoin block height) but never contacts a Bitcoin node. Non-Bitcoin
  attestations (Litecoin, Ethereum/Keccak-256) are recognised only enough to be
  preserved or clearly rejected, not verified.

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

