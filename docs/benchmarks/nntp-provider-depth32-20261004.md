# NNTP review validation — 2026-10-04

## Isolation and privacy

Provider checks used the PR branch in an isolated PHP container and one
connection at a time. Credentials were read from existing enabled-server
configuration and streamed directly into the test process through stdin.
No credentials were put in arguments, environment variables, Git, or reports.
No production configuration, database rows, cursor, cache, image, or scheduled
retriever state was changed. No live POST was attempted.

Reports deliberately contain only public provider hostnames, sample counters,
TLS verification flags, outcome counts, connection counts, and timings.
They exclude usernames, passwords, account identifiers, private hostnames,
local user paths, article/message IDs, and article bodies.

## Live depth-32 receive checks

The driver is [utils/nntp_provider_smoke.php](../../utils/nntp_provider_smoke.php).
It requires `--read-only`, accepts an array of normal transport configuration
objects on stdin, and never loads application database settings or posting.
Each endpoint received three 1,000-article samples from `free.usenet`, with
implicit TLS and certificate/hostname verification enabled. Each sample used
one connection, XOVER followed by pipelined ARTICLE requests at fixed depth 32.

| Provider endpoint | Samples | Articles received | Retries | Unresolved | Errors | Sample seconds (min–max) |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| secure.usenetserver.com | 3 | 3000 | 0 | 0 | 0 | 21.606–35.924 |
| nl.newsdemon.com | 3 | 3000 | 0 | 0 | 0 | 26.073–36.981 |
| usenetserver.tweaknews.eu | 3 | 3000 | 0 | 0 | 0 | 17.285–22.443 |

All nine samples passed: 9,000 complete articles, no missing articles in these
samples, no retries, no unresolved work, and no reported errors.
These are three provider endpoints, not a claim of independent backbones,
exhaustive provider coverage, or known poor pipelining implementations.
Historical throughput benchmarks are separate from this compatibility check.

## Deterministic scenario coverage

The final offline suite ran with external networking disabled, no database
connection and no provider credentials: PHP 8.5.4, PHPUnit 11.5.56,
**76 tests / 426 assertions**, no failures or skips. Deprecation notices remain.
The sanitized full test listing is
[nntp-review-offline-20261004.txt](nntp-review-offline-20261004.txt).

- [Configuration parity](../../tests/Services/Nntp/ServicesNntpConfigurationParityTest.php):
  plain/authenticated/unauthenticated NNTP, AUTHINFO success codes 281/381,
  authentication rejection, implicit TLS and STARTTLS, bounded handshake
  timeout, trusted/untrusted certificates, correct/incorrect hostnames,
  explicit verification flags, role-specific defaults, configured server
  values, fixed pipeline settings, pool reuse and header-server fallback.
  Local certificates are generated per test; private trust is confined to the
  test subprocess. System trust stores are not changed.
- [Transport](../../tests/Services/Nntp/ServicesNntpPipelinedTransportTest.php):
  completed versus interrupted responses, unexpected response codes, FIFO
  success/430/success, reconnect with group reselection, direct 430 no-retry,
  safe operational logging and POST framing/dot-stuffing.
- [Recovery](../../tests/Services/Nntp/ServicesNntpPipelinedRecoveryTest.php):
  retry only unresolved articles, timeout fallback to window 1, no 430 retries,
  final-article deferral, reconnect failure, and retained completed outcomes.
- [Batch cursor](../../tests/Services/Retriever/ServicesRetrieverPipelinedArticleBatchTest.php):
  terminal missing/malformed articles and cursor stop before unresolved work,
  including later successful work and failed reconnects.
- [Comment retrieval](../../tests/Services/Retriever/ServicesRetrieverCommentsPipelinedTest.php):
  capture parity, deduplication, failed-commit checkpoint safety, malformed
  payloads, cursor gaps, and the existing `buggy` extra-comment cleanup policy.
- [Settings](../../tests/Services/Settings/ServicesSettingsBaseTest.php):
  default/bounded/invalid pipeline settings.
- [Smoke-driver guard](../../tests/Utils/PipelinedCommentsBenchmarkTest.php):
  provider smoke cannot run without explicit `--read-only`.

## Issues found and corrected

1. An unfinished non-blocking STARTTLS handshake could return 0 and be treated
   as failure. Connection setup now completes the handshake in blocking mode
   with the configured timeout, then immediately restores non-blocking article
   I/O. Successful, rejected and stalled handshakes are regression-tested.
2. When `verifyname` was absent, the replacement pool used false for every
   role. The existing pool effectively uses false for headers, but true for
   separately configured binary/posting servers. These role-specific defaults
   are restored, while explicit choices and fallback reuse are preserved.

## Provider diagnostics and recovery

A failing pipeline emits `nntp.article.pipeline.failure` with sanitized
operation/role/group/window/count/classification context. A disconnect or
timeout preserves completed terminal results and retries only unresolved IDs:
one retry at configured depth, then one at depth 1. Exhausted work stays
unresolved and cannot advance the cursor past the gap. For a provider that
consistently fails pipelining, set that server's article pipeline depth to
`1` in NNTP settings and inspect the logged failure classification. Setting 1
disables pipelining; recovery fallback does not persistently retune settings.

## Reproduce offline checks

From the checkout, with a PHP/OpenSSL/pcntl test image and PHPUnit PHAR:

```sh
docker run --rm --network none \
  -v "$PWD":/work:ro -v "$PHPUNIT_PHAR":/tmp/phpunit.phar:ro \
  -w /work --entrypoint php "$SPOTWEB_TEST_IMAGE" \
  /tmp/phpunit.phar --do-not-cache-result --bootstrap vendor/autoload.php --testdox tests
```

For provider checks, pipe server configurations from a secure configuration
reader into the following command; do not include credentials in arguments
or publish the input. The input requires `host`, `port`, `enc: "ssl"`,
`user`, `pass`, and `verifyname: true` for each server.

```sh
docker run --rm -i -v "$PWD":/work:ro -w /work \
  --entrypoint php "$SPOTWEB_TEST_IMAGE" \
  utils/nntp_provider_smoke.php --read-only
```

The script exits nonzero on failed/incomplete samples. It is a receive-only
compatibility driver, not a posting test or a database migration utility.

## Remaining live-test boundary

Real posting has not been performed. Fixture POST framing does not prove
acceptance by a real posting service or end-to-end Spotweb post processing.
A posting-capable maintainer/user is welcome to validate spot/comment/report
posting in an appropriate test group. POST is deliberately never automatically
replayed after submission, to avoid duplicate posts.

## Sanitized raw JSONL

```jsonl
{"provider_host":"secure.usenetserver.com","sample":1,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":21.981}
{"provider_host":"secure.usenetserver.com","sample":2,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":21.606}
{"provider_host":"secure.usenetserver.com","sample":3,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":35.924}
{"provider_host":"nl.newsdemon.com","sample":1,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":26.073}
{"provider_host":"nl.newsdemon.com","sample":2,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":26.755}
{"provider_host":"nl.newsdemon.com","sample":3,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":36.981}
{"provider_host":"usenetserver.tweaknews.eu","sample":1,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":17.285}
{"provider_host":"usenetserver.tweaknews.eu","sample":2,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":22.443}
{"provider_host":"usenetserver.tweaknews.eu","sample":3,"window":32,"tls":true,"certificate_verification":true,"headers":1000,"articles_found":1000,"missing_430":0,"terminal":1000,"unresolved":0,"retries":0,"connections":1,"errors":0,"passed":true,"seconds":18.711}
```
