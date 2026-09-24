# The elephstamp command, from the PHAR built by the release workflow
# (.github/workflows/release.yml). Build it first: `box compile`.
# The official Debian (trixie-slim) based image: the default flavour behind
# php:8.5-cli and php:latest, glibc rather than musl. It ships the curl and
# mbstring extensions (checked by the release smoke test), so no RUN step.
FROM php:8.5-cli-trixie

COPY --chmod=0755 build/elephstamp.phar /usr/local/bin/elephstamp

WORKDIR /data

ENTRYPOINT ["elephstamp"]
