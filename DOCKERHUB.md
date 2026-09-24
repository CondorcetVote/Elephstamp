# ElephStamp

**Prove that a file existed at a given time, anchored forever in the Bitcoin
blockchain.** No account, no fee, no trusted third party: just a small `.ots`
proof next to your file, which anyone can check independently.

`elephstamp` is a friendly command-line tool for
[OpenTimestamps](https://opentimestamps.org/), the open standard for
blockchain timestamping. Stamp a contract, a source release, a dataset or a
photo today; collect the Bitcoin confirmation a few hours later; verify it
whenever you need to, years from now. Its proofs are byte-for-byte
interoperable with the reference `ots` client, so you are never locked in.

## Why ElephStamp

- **Your own Bitcoin node is optional.** Verify against it, pruned or not, for
  a check that trusts nobody else; or, when you do not run one, against public
  explorers (mempool.space, blockstream.info). Their block headers' proof of
  work is checked locally and several can be cross-checked, which bounds the
  trust you put in them.
- **Safer than the reference client.** Every answer a calendar server sends
  back is checked against the blockchain *before* it goes into your proof: a
  buggy or hostile calendar cannot leave you with a proof that fails
  verification.
- **Reports you can read.** See what every calendar did, which block and
  transaction carry your proof, and why a proof is still pending, instead of
  a raw dump.
- **Made for automation.** Stamp many files in one go, poll pending proofs
  from cron, and script everything with documented exit codes and a stable
  `--json` output.
- **Hardened.** An `.ots` file is treated as untrusted input: only whitelisted
  calendars are ever contacted, over HTTPS, with strict size and time limits.

## Quick start

Define the alias once, in your shell's profile:

```bash
alias elephstamp='docker run --rm -v "$PWD:/data" --user "$(id -u):$(id -g)" julienboudry/elephstamp'
```

The volume shows your current directory to the container; `--user` makes the
proofs it writes belong to you. Then:

```bash
elephstamp stamp contract.pdf          # → contract.pdf.ots, pending
elephstamp upgrade contract.pdf.ots    # a few hours later: collect the Bitcoin attestation
elephstamp verify contract.pdf.ots     # check it against the blockchain
```

```
 VERIFIED

 contract.pdf existed before 2026-09-18 15:27:58 UTC (Bitcoin block 967571).

  Original         contract.pdf — digest matches the proof
  Proof            sha256 digest 065470e2…, 3 Bitcoin attestations recomputed offline
  Block headers    mempool.space — trusted for block headers only
  Required depth   6 confirmations
```

`elephstamp info` explains a proof in plain words, `elephstamp tree` shows
every hash for checking by hand, and `elephstamp help <command>` lists every
option.

## The image

- **Tags:** an exact version (`1.4.0`), `1.4` and `1` for the newest release
  in that line, and `latest`.
- **Platforms:** `linux/amd64`, `linux/arm64`, `linux/riscv64`.
- **Contents:** the release's `elephstamp.phar` on the official Debian-based
  PHP image, nothing else.
- **Provenance:** every image carries a signed build attestation, checkable
  with `gh attestation verify oci://docker.io/julienboudry/elephstamp:latest --repo CondorcetVote/Elephstamp`.

## Learn more

- [Docker usage in detail](https://github.com/CondorcetVote/Elephstamp/blob/main/CLI-INSTALL.md#docker-image):
  paths, networking, reaching a Bitcoin node, building the image yourself.
- [Command-line guide](https://github.com/CondorcetVote/Elephstamp/blob/main/CLI.md):
  every command, option, JSON output and exit code.
- [ElephStamp on GitHub](https://github.com/CondorcetVote/Elephstamp): also a
  PHP library, with other installation methods and the changelog. MIT licensed.
