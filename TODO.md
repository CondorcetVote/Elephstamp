# TODO

## Security

- [ ] Widen the malformed-proof test corpus. The parser is already bounded
      (`Timestamp::RECURSION_LIMIT` = 256, `Receipt::MAX_RECEIPT_BYTES` =
      1 MB, `HttpCalendarClient::MAX_RESPONSE_BYTES` = 10 kB, varbytes caps
      per operation), but only one test exercises the depth limit. Add a
      corpus of hostile `.ots` files (max depth, max width, huge
      append/prepend arguments, truncated streams, unknown tags) and
      consider a lightweight fuzz run in CI to keep those guards honest.

## Library & CLI

- [ ] `upgradeMany()`: poll several receipts in one call, batching requests
      per calendar and fetching the chain tip once. Above all, let the CLI
      take several proofs: `elephstamp upgrade *.ots`, with a per-file
      report and the exit codes kept (`2` when at least one proof is still
      pending). Mirror what `stampMany()` / `verify` already do for batches.

## Distribution (CI)

- [ ] Build a `elephstamp.phar` on every release (GitHub Actions job on
      tag push, e.g. `box`), attach it to the GitHub release, and sign it or
      publish its checksum. Document the install path in `CLI.md`.
- [ ] Publish a Docker image exposing the CLI (`docker run ... elephstamp
      stamp file`) on every release, from the same CI, e.g. to GHCR.
      Document usage and volume mounting in `CLI.md`.
