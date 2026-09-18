# ElephStamp — command-line guide

`elephstamp` puts the whole library in your shell: create timestamp proofs,
collect their Bitcoin confirmations, and understand exactly what every
calendar server has done with them. For the PHP API, see
[LIBRARY.md](LIBRARY.md).

## Contents

- [Installation](#installation)
- [At a glance](#at-a-glance)
- [Conventions](#conventions)
- [`stamp` — create proofs](#stamp--create-proofs)
- [`upgrade` — collect confirmations](#upgrade--collect-confirmations)
- [`verify` — check against the blockchain](#verify--check-against-the-blockchain)
- [`info` — understand a proof](#info--understand-a-proof)
- [`tree` — every hash](#tree--every-hash)
- [`calendars` — the defaults](#calendars--the-defaults)
- [JSON output](#json-output)
- [Security: the upgrade whitelist](#security-the-upgrade-whitelist)
- [Automating with cron](#automating-with-cron)
- [Shell completion](#shell-completion)
- [What the tool does not do](#what-the-tool-does-not-do)

## Installation

As a standalone tool:

```bash
composer global require condorcet-vote/elephstamp
elephstamp --help
```

Make sure Composer's global `bin` directory (`composer global config bin-dir
--absolute`) is on your `PATH`.

Inside a project that already depends on the library, the command is at
`vendor/bin/elephstamp`. It needs PHP 8.5+ with the `mbstring` extension.

## At a glance

```bash
elephstamp stamp contract.pdf            # → contract.pdf.ots, pending
# ... a few hours later ...
elephstamp upgrade contract.pdf.ots      # collects the Bitcoin attestation
elephstamp verify contract.pdf.ots       # checks it against the blockchain via a block explorer
elephstamp info contract.pdf.ots         # what the proof says, calendar by calendar
elephstamp tree contract.pdf.ots         # every operation and hash, for checking by hand
```

## Conventions

- `elephstamp` alone lists the commands; `elephstamp help <command>` shows every
  option of one, with examples.
- Every command that reads proofs accepts **several** `.ots` files at once
  (`proofs/*.ots` works) and reports each in turn.
- File digests and merkle roots are always printed in full. Only the calendar
  **commitments** in tables are abbreviated to `first8…last8`, because they are
  long and there is one per calendar; pass `-v` for full values, or use `tree`,
  which never abbreviates.
- Output is coloured on a terminal; `--no-ansi` disables it, `--json` (where
  available) replaces it with a machine-readable document.
- Exit codes are part of the contract and listed for each command below.
  Across all commands: `0` success, `1` bad usage or an error (unreadable file,
  write failure, too few calendars, failed verification); `upgrade` and
  `verify` add `2` for "not yet": still pending, awaiting confirmations,
  source unreachable.

## `stamp` — create proofs

```bash
elephstamp stamp contract.pdf                 # writes contract.pdf.ots
elephstamp stamp a.pdf b.pdf c.pdf            # one calendar submission, three proofs
elephstamp stamp contract.pdf -o proofs/contract.ots
elephstamp stamp --digest 03ba204e…6ab340 -o hello.ots
elephstamp stamp contract.pdf -c https://ots.internal.example -m 1
```

Hashes each file as a stream (large files are fine), submits the commitment
to the calendars and writes one proof per file, next to it as `<file>.ots`.

| Option | Effect |
| --- | --- |
| `-o, --output=PATH` | Write the proof here instead of `<file>.ots`. Single file or digest only. |
| `--digest=HEX` | Timestamp a 64-character hex SHA-256 digest you already computed, instead of a file. Defaults to writing `<hex>.ots`. |
| `--no-nonce` | Commit to the plain file hash. The calendars, and the sibling proofs of a batch, then learn the real digest. |
| `-c, --calendar=URL` | Calendar to submit to. Repeatable; replaces the default list. Must be `https`. |
| `-m, --required=N` | How many calendars must accept the stamp (the "m" of m-of-n). Default `2`, or `1` with a single calendar. |
| `--timeout=SECONDS` | Give up on a calendar after this long (idle and total). |
| `-f, --force` | Overwrite an existing `.ots`. Without it the command refuses and writes nothing. |

Several files share one merkle tree and thus a single calendar submission,
but each gets an independent, standalone proof. Later, each proof is upgraded
on its own; they all turn complete at the same block.

A fresh proof is **pending**: the calendars have recorded the commitment,
Bitcoin has not confirmed it yet, which usually takes a few hours. The report
names the calendars that accepted the submission and the exact `upgrade`
command to run later:

```
  File           sha256 digest       Proof written to
  contract.pdf   357ebe89…b412c788   contract.pdf.ots

 Accepted by 4 calendars:
 * https://alice.btc.calendar.opentimestamps.org
 * https://bob.btc.calendar.opentimestamps.org
 * https://finney.calendar.eternitywall.com
 * https://btc.calendar.catallaxy.com

 [OK] 1 proof written. Status: pending — Bitcoin confirmation usually takes a few hours.
```

Calendars are contacted concurrently, and one that is down is tolerated as
long as `--required` of them accept. Below that threshold nothing is written
and the command exits `1` with each calendar's error.

**Exit codes:** `0` every proof written; `1` nothing written.

## `upgrade` — collect confirmations

```bash
elephstamp upgrade contract.pdf.ots
elephstamp upgrade proofs/*.ots
elephstamp upgrade contract.pdf.ots --dry-run
elephstamp upgrade contract.pdf.ots --all
elephstamp upgrade contract.pdf.ots -l 'https://*.internal.example' --timeout 5
```

For each proof, polls every calendar it is pending on and prints one line per
calendar with what it answered:

```
  Calendar                                        Commitment          Answer
  https://alice.btc.calendar.opentimestamps.org   6aada194…779bcb5c   upgraded — Bitcoin block 912345
  https://bob.btc.calendar.opentimestamps.org     6aada194…9b8504a8   still pending — not confirmed yet
  https://finney.calendar.eternitywall.com        6aada193…4fc5e50b   failed — Calendar https://finney…: Idle timeout reached
  https://ots.private.example                     6aada194…95adc728   skipped — not on the whitelist, not contacted

 [OK] Now complete: anchored in Bitcoin block 912345. Saved to contract.pdf.ots.
```

| Answer | Meaning |
| --- | --- |
| `upgraded` | The calendar returned attestations that were merged into the proof. |
| `unchanged` | The calendar answered with material the proof already held. |
| `still pending` | The calendar has nothing yet for this commitment. Try later. |
| `failed` | Network or protocol error; the calendar's message follows. |
| `rejected` | The calendar returned a proof for a *different* digest. Hostile or corrupt, discarded. |
| `skipped` | The calendar's host is not on the whitelist, so it was never contacted. |
| `confirmed` | A Bitcoin attestation already hangs below this submission, nothing to ask. Only with `--all`. |

A proof that gained attestations is rewritten in place, atomically. A proof
that is already complete is reported as such and not polled: one Bitcoin
attestation is all a proof needs, so the calendars still marked pending in it
are never contacted again.

To collect the other calendars' attestations anyway, pass `--all`. Each
calendar's transaction usually lands in a slightly different block, and the
proof then carries one attestation per branch (`info` lists them all,
`bitcoinBlockHeight` being the lowest):

```
  Calendar                                        Commitment          Answer
  https://finney.calendar.eternitywall.com        6aad431a…28af8004   upgraded — Bitcoin block 967572
  https://btc.calendar.catallaxy.com              6aad431a…9c4a52ee   upgraded — Bitcoin block 967603
  https://bob.btc.calendar.opentimestamps.org     6aad431a…57692c88   confirmed — already anchored in Bitcoin block 967571, not polled
  https://alice.btc.calendar.opentimestamps.org   6aad431b…fbf30fbc   still pending — not confirmed yet

 [OK] Complete (Bitcoin block 967571): 2 new attestations merged. Confirmed through 3 of 4 calendars. Saved to contract.pdf.ots.
``` When nothing
changed, the note explains why (confirmation still pending, unreachable
calendars, skipped hosts) and what to do about it.

| Option | Effect |
| --- | --- |
| `--dry-run` | Poll and report, but write nothing. |
| `--all` | Also poll the calendars still pending in an already complete proof, to collect every attestation. |
| `-o, --output=PATH` | Write the upgraded proof here instead of in place. Single proof only. |
| `-l, --whitelist=PATTERN` | Allow contacting calendars matching this `https://host` pattern (globs allowed). Repeatable. |
| `--no-default-whitelist` | Drop the built-in public-operator patterns; only `--whitelist` ones remain. |
| `--timeout=SECONDS` | Give up on a calendar after this long. |
| `--json` | Machine-readable output, see [JSON output](#json-output). |

With several proofs a summary closes the report:

```
 3 proofs: 2 complete, 1 still pending.
```

**Exit codes:** `0` every proof is complete; `2` at least one is still pending
(and nothing went wrong); `1` bad usage, or a proof could not be read or
written. This makes `upgrade` easy to script, see
[Automating with cron](#automating-with-cron).

## `verify` — check against the blockchain

```bash
elephstamp verify contract.pdf.ots                          # original file found next to it
elephstamp verify contract.pdf.ots --file archive/v2.pdf
elephstamp verify contract.pdf.ots --digest 065470e2…       # without the file
elephstamp verify contract.pdf.ots --explorer blockstream   # default: mempool
elephstamp verify contract.pdf.ots -e mempool -e blockstream            # both must agree
elephstamp verify contract.pdf.ots --explorer-url https://esplora.internal/api
elephstamp verify proofs/*.ots --min-confirmations 1 --json
```

Recomputes everything the proof contains, offline: the file digest, every
operation, the transaction, the merkle branch, down to the merkle root of each
block the proof names. Then asks a block explorer for the header of those
blocks. A proof is **verified** when a block's merkle root equals the one the
proof leads to, and the block is buried under enough confirmations. The
verdict opens the report as a full-width banner:

```
 VERIFIED

 contract.pdf existed before 2026-09-18 15:27:58 UTC (Bitcoin block 967571).

  Original         contract.pdf — digest matches the proof
  Proof            sha256 digest 065470e2…, 3 Bitcoin attestations recomputed offline
  Block headers    mempool.space and blockstream.info (cross-checked) — a third party, trusted for block headers only
  Required depth   6 confirmations

  Block    Merkle root (proof)   Merkle root (block)   Mined at (UTC)        Confirmations   Result
  967571   098f1d27…7ad0bfab     098f1d27…7ad0bfab     2026-09-18 15:27:58   50              verified — merkle roots match
  967572   4c0e81a2…91d73f5e     4c0e81a2…91d73f5e     2026-09-18 15:31:09   49              verified — merkle roots match
  967603   b7d2c0f1…2ae4c9d0     b7d2c0f1…2ae4c9d0     2026-09-18 21:02:44   18              verified — merkle roots match
```

`-v` prints full merkle roots and the block hashes.

| Banner | Meaning | Exit |
| --- | --- | --- |
| `VERIFIED` | The file matches the proof (when checked) and at least one block confirms it. The date is the time of the earliest such block. | `0` |
| `AWAITING CONFIRMATIONS` | The merkle root matches but every matching block is still shallower than `--min-confirmations`. | `2` |
| `PENDING` | No Bitcoin attestation yet; run `upgrade` first. | `2` |
| `INCONCLUSIVE` | Attestations exist but no header could be checked (explorer unreachable, unknown block). | `2` |
| `VERIFICATION FAILED` | The file is not the one the proof commits to, or a block's merkle root differs from the proof's: corrupt, forged, or wrong block. | `1` |

Per block, the *Result* column reads `verified`, `matches, awaiting
confirmations (n of 6)`, `MISMATCH`, `unavailable — <reason>` or `not
computable` (below an operation this tool cannot compute).

**What is trusted.** Only the block header comes from outside, and the
explorer is a third party you trust for it. Two things bound that trust. The
explorer's raw 80-byte header is parsed locally: its hash is recomputed and
checked against the difficulty the header itself declares, so an explorer
cannot slip in a bogus merkle root without forging a valid proof of work.
And several explorers can be required to agree: repeat `--explorer` (or add
`--explorer-url`) and the verification only passes if they all return the
same header. Verifying against your own Bitcoin node is planned for a later
release.

| Option | Effect |
| --- | --- |
| `--file=PATH` | The original file the proof should be for. Found automatically as `<proof without .ots>` when present. Single proof only. |
| `--digest=HEX` | Its SHA-256 digest, when you do not have the file. Single proof only. |
| `-e, --explorer=NAME` | `mempool` (default) or `blockstream`. Repeatable: all named explorers must agree. |
| `--explorer-url=URL` | Any other Esplora-compatible API, e.g. a self-hosted instance. Repeatable, `https` only. |
| `--min-confirmations=N` | Depth a block needs before its attestation counts, itself included. Default `6`. |
| `--timeout=SECONDS` | Give up on an explorer after this long. |
| `--json` | Machine-readable output, see [JSON output](#json-output). |

Without an original file or digest, the banner says so: the proof itself is
verified, not that it belongs to a given file.

**Exit codes:** `0` verified; `1` failed, bad usage, or an unreadable proof;
`2` not verifiable yet.

## `info` — understand a proof

```bash
elephstamp info contract.pdf.ots
elephstamp info contract.pdf.ots --file archive/contract-2024.pdf
elephstamp info proofs/*.ots
elephstamp info contract.pdf.ots -v        # full commitments in the calendar table
```

Works **offline**: nothing is contacted. The report has four parts.

**Overview.** Status, hash algorithm, file digest, the original file and
whether its digest still matches, proof size:

```
  Status          pending — waiting on 2 calendars; run "upgrade" to poll
  File hash       sha256
  File digest     d32fee9a827f5a0d580f80beb7edce662dd99fcd6591e4ef8a6244403df0b7c9
  Original file   merkle1.txt — digest matches
  Proof size      335 bytes
```

The original file is found automatically when it sits next to the proof
(`contract.pdf` for `contract.pdf.ots`); otherwise the line reads
`not checked: contract.pdf not found next to the proof (use --file)` and you
can point elsewhere with `--file`. When the file does **not** match, a
full-width red banner opens the report, before anything else:

```
 DIGEST MISMATCH

 archive/contract-2024.pdf is not the file this proof was made for.

 Its sha256 digest differs from the one the proof commits to; the proof says
 nothing about this file.
```

The rest of the report still follows (the proof itself may be perfectly
valid, just for another file) and the command exits `1`. A complete proof
reads `complete — anchored in Bitcoin block 358391`.

**Calendar submissions.** One row per pending attestation:

```
  Calendar                                        Recorded at (calendar clock, UTC)   Commitment          State
  https://alice.btc.calendar.opentimestamps.org   2016-09-14 17:03:27                 57d982df…55590477   pending, upgradable
  https://bob.btc.calendar.opentimestamps.org     2016-09-14 17:03:28                 57d982e0…3071762a   confirmed in block 430000
```

- *Recorded at* is the time the calendar wrote into the proof when it accepted
  the commitment (the reference calendar server prepends a 4-byte timestamp).
  It is informational, read from the proof and never verified; it shows as
  `unknown` when a proof does not follow that layout.
- *Commitment* is the digest that calendar holds, i.e. the value it can be
  asked about.
- *State* is one of `pending, upgradable`; `confirmed in block N` (a Bitcoin
  attestation already hangs below this submission); `pending, no longer
  polled: the proof is already complete` (see below); `not upgradable:
  calendar not on the whitelist`; or `not upgradable: sits below an operation
  this tool cannot compute` (a `keccak256` edge, used by non-Bitcoin notaries).

A single Bitcoin attestation makes a proof complete: it is then fully
verifiable on its own, and `upgrade` stops polling the other calendars, as the
reference client does. Their submissions stay in the proof as a record, marked
*no longer polled*; `upgrade --all` fetches them. The status line of a
complete proof says how many of the calendars got that far, e.g.
`complete — anchored in Bitcoin block 967571 (confirmed through 1 of 4 calendars)`.

**Bitcoin attestations.** For each Bitcoin attestation: the block height the
proof claims, the id of the transaction carrying the commitment, and the
block's merkle root, both in the byte order block explorers display:

```
  Block height     967571
  Transaction id   0088fa0ae5bb33378fde6c722088f676d6525f75d48cd2c5125d675c69d243f7
  Merkle root      098f1d278e9fbe9493f1b4fc0ddb3af3d9bb203cf254be2a4f48a3037ad0bfab
  Look it up       https://mempool.space/tx/0088fa0ae5bb33378fde6c722088f676d6525f75d48cd2c5125d675c69d243f7

 Recomputed from the proof, not verified: open the transaction and check the block it sits in,
 or open the block by its height and compare its merkle root (explorers do not search by merkle root).
```

The proof does not name the transaction; the tool recognises its raw bytes on
the proof path and hashes them, exactly as a verifier would. The transaction
id is the value to paste into an explorer: check that the transaction sits in
the block the proof claims. The merkle root is what a node compares with the
block header; explorers do not search by it, so open the block by its height
and compare the field. When a proof does not embed a recognisable transaction
the id reads `unknown` and the link points at the block instead.

**Unsupported attestations.** Attestations of a type this tool does not
understand (Litecoin, Ethereum, ...) are listed with their tag and payload
size. They are preserved verbatim in the proof, just not interpreted.

| Option | Effect |
| --- | --- |
| `--file=PATH` | The original file to check the digest against. Single proof only. |
| `-l, --whitelist=PATTERN`, `--no-default-whitelist` | Same as for `upgrade`; they decide which submissions are reported as *upgradable*. |
| `--json` | Machine-readable output, see [JSON output](#json-output). |

**Exit codes:** `0` fine; `1` bad usage, a proof could not be read, or a digest
mismatch was found.

## `tree` — every hash

```bash
elephstamp tree contract.pdf.ots
elephstamp tree contract.pdf.ots --no-hashes
elephstamp tree contract.pdf.ots --plain
```

The raw commitment tree, for those who want to verify a proof step by step:
each operation (`append`, `prepend`, `sha256`, `ripemd160`, ...) followed by
the hash it produces, from the file digest down to the attestations. Nothing
is abbreviated.

```
file sha256 digest d288b2ee212b01e3e5f6d333df3a4d53f292cc3f07b09013c0b40c8e7dcb9c03
├── append 46d842bd5d8377e0f42041bec9bda667 = d288b2ee…9c0346d842bd5d8377e0f42041bec9bda667
├── sha256 = 95e2b314af1a11524778ade82197444350d46c60894e7839863a401746e5c00f
├── append 332c572f9c4b8d5db9d99758d48fff34 = 95e2b314…c00f332c572f9c4b8d5db9d99758d48fff34
│   ├── sha256 = 173f127e0e8832929232858ca0b7241229d535b22073144a1a433b5aaa5d6cf4
│   ├── prepend 57e89f38 = 57e89f38173f127e0e8832929232858ca0b7241229d535b22073144a1a433b5aaa5d6cf4
│   ├── append 73c6dc4d0cbc29f0 = 57e89f38173f…6cf473c6dc4d0cbc29f0
│   └── pending attestation → https://bob.btc.calendar.opentimestamps.org
└── append e7ad29076f188033d20767602ca3a8e0 = 95e2b314…c00fe7ad29076f188033d20767602ca3a8e0
    ├── sha256 = c0b849c680f7c6e13c719a3bfa96ce53c83095eca7c5a244ca991aeebbc87a4e
    ├── prepend 57e89f37 = …
    ├── append 62df56371ae23d8d = …
    └── unknown attestation (tag 0102030405060708)
```

(The example above is abbreviated for this page; the real output prints every
hash in full.) A linear chain of operations stays flat; only a real fork nests
its branches, like the reference client's `ots info`. Below a `keccak256`
edge the hashes read `(not computable)`.

| Option | Effect |
| --- | --- |
| `--no-hashes` | Operations only, without the hash each produces. |
| `--plain` | The reference client's indented text layout, identical to `Receipt::describe()`. |

**Exit codes:** `0` fine; `1` a proof could not be read.

## `calendars` — the defaults

Lists the calendar servers `stamp` submits to by default, the m-of-n
threshold, the host patterns `upgrade` and `info` trust by default, and the
block explorers `verify` can consult.

## JSON output

`info`, `upgrade` and `verify` accept `--json`. One proof yields one object; several
proofs yield a list, in argument order. A proof that could not be read yields
`{"receipt": "...", "error": "..."}` in its slot, and the exit code is `1`.

`info --json`:

```json
{
    "receipt": "merkle1.txt.ots",
    "status": "pending",
    "bitcoin_block_height": null,
    "file": {
        "hash": "sha256",
        "digest": "d32fee9a827f5a0d580f80beb7edce662dd99fcd6591e4ef8a6244403df0b7c9",
        "path": "merkle1.txt",
        "digest_matches": true
    },
    "proof_size_bytes": 335,
    "calendars": [
        {
            "url": "https://alice.btc.calendar.opentimestamps.org",
            "commitment": "57d982df8b35bc0a…b1f26e2e55590477",
            "recorded_at": "2016-09-14T17:03:27+00:00",
            "confirmed": false,
            "block_heights": [],
            "upgradable": true
        }
    ],
    "bitcoin_attestations": [
        {
            "block_height": 358391,
            "transaction_id": "7e9f0f7d…627cb2ec",
            "merkle_root": "8a1b66ec…45e47e00"
        }
    ],
    "unknown_attestations": [
        { "tag": "0102030405060708", "payload_bytes": 46 }
    ]
}
```

`file.path` and `file.digest_matches` are `null` when no original file was
checked; `digest_matches` is also `null` when the file could not be read.
`recorded_at`, `commitment` and `transaction_id` are `null` when they cannot
be determined.

`upgrade --json`:

```json
{
    "receipt": "contract.pdf.ots",
    "status": "complete",
    "bitcoin_block_height": 912345,
    "was_already_complete": false,
    "changed": true,
    "saved_to": "contract.pdf.ots",
    "calendars": [
        {
            "url": "https://alice.btc.calendar.opentimestamps.org",
            "commitment": "6aada194…779bcb5c",
            "outcome": "upgraded",
            "block_height": 912345,
            "error": null
        }
    ]
}
```

`outcome` is one of `upgraded`, `unchanged`, `pending`, `failed`, `rejected`,
`skipped`, `confirmed`; `error` is set for `failed` and `rejected`;
`block_height` is the lowest block now attested below that submission, when
there is one. `saved_to` is `null`
when nothing was written (no change, or `--dry-run`).

`verify --json`:

```json
{
    "receipt": "contract.pdf.ots",
    "verdict": "verified",
    "attested_at": "2026-09-18T15:27:58+00:00",
    "attesting_block_height": 967571,
    "file": {
        "hash": "sha256",
        "digest": "065470e2…5d7f73e0",
        "subject": "contract.pdf",
        "digest_matches": true
    },
    "source": "mempool.space",
    "required_confirmations": 6,
    "bitcoin_attestations": [
        {
            "block_height": 967571,
            "outcome": "verified",
            "proof_merkle_root": "098f1d27…7ad0bfab",
            "block_merkle_root": "098f1d27…7ad0bfab",
            "block_hash": "00000000…cefa8364",
            "block_time": "2026-09-18T15:27:58+00:00",
            "confirmations": 50,
            "transaction_id": "0088fa0a…69d243f7",
            "error": null
        }
    ]
}
```

`verdict` is one of `verified`, `awaiting_confirmations`, `pending`,
`inconclusive`, `failed`; `outcome` one of `verified`,
`awaiting_confirmations`, `merkle_root_mismatch`, `block_unavailable`,
`not_computable`. `subject` is the file path or `digest <hex>`, and
`digest_matches` is `null` when nothing was checked. `attested_at` is only
set for a verified proof.

## Security: the upgrade whitelist

A proof embeds the URIs of the calendars to poll. A proof is untrusted input:
if those URIs were contacted blindly, a hostile `.ots` could make the tool send
requests to arbitrary hosts (an SSRF vector). `upgrade` therefore only
contacts hosts matching a whitelist, and `info` reports the others as *not
upgradable*.

The default whitelist covers the known public operators:

```
https://*.calendar.opentimestamps.org
https://*.calendar.eternitywall.com
https://*.calendar.catallaxy.com
```

Add your own with `-l/--whitelist` (repeatable). Patterns are `https://host`
URLs whose host may contain shell globs; an explicit port must be listed
explicitly; `http://` is refused. `--no-default-whitelist` keeps only your
patterns:

```bash
elephstamp upgrade proof.ots --no-default-whitelist -l 'https://ots.internal.example'
```

Calendar traffic is hardened as in the library: `https` only, redirects never
followed, responses capped at 10 kB, timeouts on idle and total duration.

## Automating with cron

`upgrade` exits `2` while a proof is pending and `0` once it is complete, so a
periodic job can keep polling until it is done:

```
# Every hour, poll every pending proof. Exit code 2 (still pending) is normal here.
0 * * * *  cd /srv/proofs && elephstamp upgrade --no-ansi *.ots >> upgrade.log 2>&1
```

In a script, the exit code alone tells the story:

```bash
if elephstamp upgrade --quiet contract.pdf.ots; then
    echo "complete"
fi
```

For machine consumption, prefer `--json` and read `status` and
`calendars[].outcome`.

## Shell completion

Symfony Console provides completion for bash, zsh and fish:

```bash
elephstamp completion bash > ~/.local/share/bash-completion/completions/elephstamp
# or, for the current shell only:
eval "$(elephstamp completion bash)"
```

## What the tool does not do

- **No verification against your own node yet.** `verify` relies on public
  block explorers for block headers; asking a node you run is planned for a
  later release. `info` and `upgrade` themselves never check anything on-chain.
- **No non-Bitcoin notaries.** Litecoin or Ethereum attestations are preserved
  and listed as unsupported, never interpreted or upgraded.
- **No pruning or editing of proofs**: the tool only ever adds attestations.
