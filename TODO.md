# TODO

## Library & CLI

- [ ] `upgradeMany()`: poll several receipts in one call, batching requests
      per calendar and fetching the chain tip once. Above all, let the CLI
      take several proofs: `elephstamp upgrade *.ots`, with a per-file
      report and the exit codes kept (`2` when at least one proof is still
      pending). Mirror what `stampMany()` / `verify` already do for batches.

## Distribution (CI)

- [x] Build a `elephstamp.phar` on every release (GitHub Actions job on
      tag push, e.g. `box`), attach it to the GitHub release, and sign it or
      publish its checksum. Document the install path in `CLI.md`.
- [ ] Publish a Docker image exposing the CLI (`docker run ... elephstamp
      stamp file`) on every release, from the same CI, e.g. to GHCR.
      Document usage and volume mounting in `CLI.md`.
