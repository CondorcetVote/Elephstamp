# Changelog

## [1.3.0] — 2026-09-23

### Added

- **Verification against your own Bitcoin node.**
  `Verify\BitcoinRpcBlockHeaderSource` fetches block headers from any node
  speaking the Bitcoin Core JSON-RPC protocol (`getblockhash`,
  `getblockheader`, `getblockcount`), including a pruned node or a hosted RPC
  provider. Credentials go in the URL, as a user and password, or as the path
  to Bitcoin Core's `.cookie` file. Plain `http` is accepted for local and
  private hosts only; anything else must be `https`. The raw header's proof
  of work is checked locally, as with explorers, and a node can be
  cross-checked with explorers through `CrossCheckingBlockHeaderSource`.
- CLI: `verify` and `upgrade` gain `--node`, `--node-user`, `--node-password`
  and `--node-cookie`. `--node` alone replaces the explorer; together with
  `--explorer`, every source must agree.

### Changed

- CLI: the *Block headers* line of the `verify` report reads "trusted for
  block headers only" and no longer calls the source a third party, since it
  may now be your own node.
- CLI: `--json` is now a documented contract: the document is identical at
  every verbosity and every hash in it is complete. The JSON samples in
  `CLI.md` used to show abbreviated hashes the tool never actually produced.

## [1.2.0] — 2026-09-22

### Added

- **Calendar answers are verified before being merged.** `upgrade()` and
  `upgradeWithReport()` take two new parameters, `$verify` (default `true`)
  and `$requiredConfirmations`. An attestation returned by a calendar is now
  checked against the blockchain before it is merged into the proof; an answer
  that fails the check is not merged and the calendar stays pending.
- `Upgrade\UpgradeOutcome::Unconfirmed` (the block a calendar names is still
  too shallow) and `Upgrade\UpgradeOutcome::Unverifiable` (the answer could
  not be checked).
- `Upgrade\CalendarUpgradeResult::verified()`, `confirmations()` and
  `claimedBlockHeight()`.
- `Verify\Verifier::checkAnchors()` is now public: it checks a batch of
  Bitcoin attestations, whether they come from a receipt or from a calendar
  answer about to be merged. The chain tip is fetched once per batch.
- `Receipt::$path` — the `.ots` file a receipt was loaded from or last saved
  to, `null` for a receipt that never touched the disk. Set by `fromPath()`,
  `fromSplFileObject()` and `saveToPath()`.
- `Receipt::save()` — write the receipt back to its own file.
- CLI: `upgrade` gains `--no-verify`, `--min-confirmations`, `--explorer`/`-e`
  (repeatable, to require several explorers to agree) and `--explorer-url`.

### Changed

- The verification verdict is more tolerant: an attestation that disagrees no
  longer invalidates the whole proof when another attestation is verified.
  Disagreements are listed in `Verify\VerificationReport::mismatches()`.
- CLI: `--timeout` now also caps block explorer requests, not just calendars.
- `FakeCalendarClient` can reproduce the new upgrade scenarios (unconfirmed
  and unverifiable answers).
- Clearer package description in `composer.json`.

## [1.1.0] — 2026-09-19

### Added

- **The `elephstamp` command-line tool** (`bin/elephstamp`, Symfony Console)
  with the `stamp`, `upgrade`, `info`, `tree` and `calendars` commands,
  per-calendar reports and JSON output. Commands obtain their client through
  the `Console\ClientFactory` seam, so they can be tested against a fake
  calendar. `Console\Inspection\ProofInspector` provides the display analysis,
  built only from the public API.
- **Verification against the Bitcoin blockchain**: the `verify` command and
  the `Verify\` API — `Verifier`, `VerificationReport`, `Verdict`,
  `AnchorVerification` and `AnchorOutcome`.
- The `Verify\BlockHeaderSource` seam, with an Esplora driver
  (`EsploraBlockHeaderSource`, behind the `Explorer` enum for mempool.space
  and blockstream.info), `CrossCheckingBlockHeaderSource` to require several
  sources to agree, and `FakeBlockHeaderSource` for tests.
- `Verify\BlockHeader::fromRawHeader()` recomputes the block hash and checks
  the proof of work — this is what bounds the trust put in an explorer.
- `Exception\BlockSourceException`.
- `Upgrade\UpgradeReport`, `CalendarUpgradeResult` and `UpgradeOutcome`:
  `upgrade()` became a thin wrapper over the new `upgradeWithReport()`.
- Bitcoin transaction decoding: `Bitcoin\TransactionParser`, `AnchorLocator`,
  `BitcoinAnchor` and `BitcoinTransaction`.

### Changed

- Documentation split in three: `README.md` is now a short entry point, with
  the PHP API guide in `LIBRARY.md` and the command-line guide in `CLI.md`.
- Test suite moved from Pest 4 to Pest 5.

## [1.0.0] — 2026-07-15

First release.

### Added

- Wire format, byte-compatible with the reference implementation:
  `Serialization/`, `Operation/` (SHA-1, SHA-256, RIPEMD-160, Keccak-256,
  Append, Prepend, Reverse, Hexlify), `Attestation/`, `Timestamp`,
  `DetachedTimestampFile` and `Merkle/`. Round-trips are validated against
  genuine reference-client fixtures.
- The `ElephStamp` facade: stamping one or several files in a single Merkle
  tree, m-of-n calendar threshold, `upgrade()` and `Status`.
- The `Calendar\CalendarClient` batch seam, with a concurrent
  `HttpCalendarClient` built directly on the Symfony HTTP client (no PSR HTTP
  abstractions), and `FakeCalendarClient` for a deterministic, network-free
  fake mode with an explicit pending → confirmed lifecycle.
- `Random\RandomSource` (`CryptoRandomSource`, `DeterministicRandomSource`),
  on top of PHP's `Random\Randomizer`.
- Hardening: HTTPS-only `CalendarWhitelist` with port handling,
  case-insensitive and deduplicated calendar URLs, and an SSRF guard on the
  URIs embedded in an untrusted `.ots`; a receipt size cap
  (`Receipt::MAX_RECEIPT_BYTES`); a single calendar's transport error is
  captured in its `CalendarResponse` and can never abort a batch.
- GitHub Actions CI and Dependabot configuration.

[Unreleased]: https://github.com/CondorcetVote/Elephstamp/compare/v1.3.0...HEAD
[1.3.0]: https://github.com/CondorcetVote/Elephstamp/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/CondorcetVote/Elephstamp/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/CondorcetVote/Elephstamp/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/CondorcetVote/Elephstamp/releases/tag/v1.0.0
