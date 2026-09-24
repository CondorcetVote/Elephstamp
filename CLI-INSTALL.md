# Installing the `elephstamp` command

There are three ways to install the command-line tool. They all run the same
code; pick the one that suits your machine. Once it is installed, head to
[CLI.md](CLI.md) to use it.

To use ElephStamp as a PHP library in your own project, you do not need any
of this: `composer require condorcet-vote/elephstamp` is enough, see the
[README](README.md#installation).

## Contents

- [Choosing a method](#choosing-a-method)
- [Standalone PHAR](#standalone-phar)
- [Docker image](#docker-image)
- [Composer](#composer)
- [PHP extensions (PHAR and Composer)](#php-extensions-phar-and-composer)
- [Shell completion](#shell-completion)

## Choosing a method

| Method | You need | Best for |
| --- | --- | --- |
| [Standalone PHAR](#standalone-phar) | PHP 8.5+ with `mbstring`, [`curl` recommended](#php-extensions-phar-and-composer) | A single file to drop in your `PATH`. |
| [Docker image](#docker-image) | Docker | Machines without PHP 8.5. |
| [Composer](#composer) | PHP 8.5+ with `mbstring`, [`curl` recommended](#php-extensions-phar-and-composer), Composer | PHP developers, or a project already using the library. |

## Standalone PHAR

### Download and install

An `elephstamp.phar` is attached to every
[GitHub release](https://github.com/CondorcetVote/Elephstamp/releases), with
its SHA-256 checksum:

```bash
curl -LO https://github.com/CondorcetVote/Elephstamp/releases/latest/download/elephstamp.phar
curl -LO https://github.com/CondorcetVote/Elephstamp/releases/latest/download/elephstamp.phar.sha256
sha256sum -c elephstamp.phar.sha256 # optional, security
chmod +x elephstamp.phar
sudo mv elephstamp.phar /usr/local/bin/elephstamp
elephstamp --help
```

### Checking where it comes from

Each PHAR is built by the release workflow from the tagged source, and comes
with a signed build provenance attestation. With the GitHub CLI, you can check
that the file is the one that workflow produced:

```bash
gh attestation verify elephstamp.phar --repo CondorcetVote/Elephstamp
```

## Docker image

### Setting it up

The [`julienboudry/elephstamp`](https://hub.docker.com/r/julienboudry/elephstamp)
image is published on Docker Hub for every release (`linux/amd64`,
`linux/arm64` and `linux/riscv64`, on the official Debian-based PHP image).
It runs the same PHAR. Define an alias once, in your shell's profile, and
use `elephstamp` exactly as if it were installed:

```bash
alias elephstamp='docker run --rm -v "$PWD:/data" --user "$(id -u):$(id -g)" julienboudry/elephstamp'

elephstamp stamp contract.pdf
```

### What the options do

- `-v "$PWD:/data"` shows the current directory to the container, which
  works in `/data`. Without it, the tool can neither read your files nor
  write their proofs.
- `--user "$(id -u):$(id -g)"` writes the proofs as you. The container runs
  as root otherwise, and on Linux the `.ots` files would then belong to root.
  Docker Desktop (macOS, Windows) remaps file ownership, so it can do
  without.

Commands that only read (`info`, `tree`, `verify`) need no `--user`:

```bash
docker run --rm -v "$PWD:/data" julienboudry/elephstamp info contract.pdf.ots
```

### Tags

Tags follow the releases: `1.4.0` for an exact version, `1.4` and `1` for the
newest release in that line, and `latest`.

### Paths and networking

Only paths under the mounted directory are visible to the container: pass
them relative to it (`stamp docs/contract.pdf`, not an absolute host path).

Likewise, `127.0.0.1` in `--node` is the container itself: to reach a Bitcoin
node on the host, add `--network host` (Linux), or run the container on the
node's Docker network and use its service name. Mount the `.cookie` file for
`--node-cookie`.

### Checking where it comes from

The image carries a signed build provenance attestation too:

```bash
gh attestation verify oci://docker.io/julienboudry/elephstamp:latest --repo CondorcetVote/Elephstamp
```

### Building the image yourself

The [`Dockerfile`](Dockerfile) only copies `build/elephstamp.phar` onto the
official PHP image, so building the image means putting a PHAR there first.

#### From a released PHAR

```bash
git clone https://github.com/CondorcetVote/Elephstamp.git && cd Elephstamp
mkdir -p build
curl -Lo build/elephstamp.phar https://github.com/CondorcetVote/Elephstamp/releases/latest/download/elephstamp.phar
docker build -t elephstamp .
```

#### From the source

Compile the PHAR with [Box](https://github.com/box-project/box), as the
release workflow does, from a fresh clone (the install drops the development
dependencies):

```bash
git clone https://github.com/CondorcetVote/Elephstamp.git && cd Elephstamp
composer install --no-dev --optimize-autoloader
curl -LO https://github.com/box-project/box/releases/download/4.7.0/box.phar
php box.phar compile                 # → build/elephstamp.phar
docker build -t elephstamp .
```

Then run `elephstamp` instead of `julienboudry/elephstamp` in the commands
above. A PHAR built from an untagged checkout reports its version as
`dev-main`; prefix the `composer install` with `COMPOSER_ROOT_VERSION=1.4.0`
to give it another.

## Composer

### As a global tool

```bash
composer global require condorcet-vote/elephstamp
elephstamp --help
```

Make sure Composer's global `bin` directory (`composer global config bin-dir
--absolute`) is on your `PATH`.

### Inside a project

A project that already depends on the library has the command at
`vendor/bin/elephstamp`, nothing more to install.

## PHP extensions (PHAR and Composer)

### `mbstring` (required)

Both methods run on your own PHP, which needs the `mbstring` extension.

### `curl` (recommended)

With PHP's `curl` extension, calendars, block explorers and nodes are
contacted through curl: faster concurrent requests, over HTTP/2. Without it,
the tool still works, through PHP streams. Check with `php -m | grep curl`;
on Debian or Ubuntu it comes with the `php8.5-curl` package (or your
distribution's equivalent).

## Shell completion

Symfony Console provides completion for bash, zsh and fish:

```bash
elephstamp completion bash > ~/.local/share/bash-completion/completions/elephstamp
# or, for the current shell only:
eval "$(elephstamp completion bash)"
```
