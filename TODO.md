# TODO

## Library & CLI

- [ ] `upgradeMany()`: poll several receipts in one call, batching requests
      per calendar and fetching the chain tip once. Above all, let the CLI
      take several proofs: `elephstamp upgrade *.ots`, with a per-file
      report and the exit codes kept (`2` when at least one proof is still
      pending). Mirror what `stampMany()` / `verify` already do for batches.
