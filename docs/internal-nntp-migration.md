# Internal NNTP migration architecture

This branch replaces the remaining PEAR-derived NNTP stack with a single
internal Spotweb NNTP layer. It is structured for upstream review and keeps
all protocol handling in one reusable implementation.

## Dependency map

```text
Scheduled retrieve.php
  -> Services_Retriever_Base
     -> Services_Nntp_ClientPool
        -> Services_Nntp_PipelinedTransport
     -> Services_Nntp_PipelinedRecovery
     -> Services_Retriever_PipelinedArticleBatch
     -> stream-specific parser/DAO policy

Request-driven reads
  -> SpotPage/Services_Actions/Providers
     -> Services_Nntp_ClientPool
        -> Services_Nntp_SpotReader
           -> Services_Nntp_PipelinedTransport

Posting
  -> Services_Posting_Spot/Comment/Report
     -> Services_Nntp_ClientPool
        -> Services_Nntp_SpotPoster
           -> Services_Nntp_PipelinedTransport

Install/config checks
  -> SpotInstall / SpotTemplateHelper
     -> Services_Nntp_PipelinedTransport::validateServer()
```

## Layering rule

- `Services_Nntp_PipelinedTransport` owns the wire protocol: socket lifecycle,
  TLS/STARTTLS, authentication, NNTP command framing, multiline parsing,
  dot-stuffing, `GROUP`, `XOVER`, `XHDR`, `HEAD`, `BODY`, `ARTICLE`, `POST`,
  `QUIT`, idempotent direct-read retry/reconnect, and per-operation debug
  events.
- `Services_Nntp_ClientPool` owns request/process-local role selection for
  `hdr`, `bin`, and `post` servers and applies the shared pipeline-depth
  setting.
- `Services_Nntp_PipelinedRecovery`,
  `Services_Nntp_PipelinedFetchOutcome`, and
  `Services_Nntp_PipelinedFetchException` own typed terminal/unresolved
  results and categorized recovery errors.
- `Services_Nntp_SpotReader` and `Services_Nntp_SpotPoster` are thin Spotweb
  domain adapters. They may parse/construct Spotweb messages, but must not
  contain socket, TLS, auth, multiline, dot-stuffing, response-code, retry, or
  reconnect logic.
- Scheduled retriever classes are limited to cursor selection, parser policy,
  DAO persistence/linking, and contiguous cursor advancement.

The old `Services_Nntp_Engine`, `Services_Nntp_EnginePool`,
`Services_Nntp_SpotReading`, `Services_Nntp_SpotPosting`,
`spotweb/nntp`, and `Net_NNTP` code paths are removed. The architecture test
prevents those names and low-level protocol primitives from reappearing in
runtime code or adapters.

## Logging and sensitive data

NNTP library operations use `SpotDebug::msg()` through the central transport.
Debug context includes role, current group, operation family, status/timing,
pipeline depth/counts, and recovery class where relevant. Credentials,
`AUTHINFO` payloads, full articles, NZB payloads, image payloads, and posted
content must not be logged. Message IDs are kept out of normal operation logs;
they may only be added later at explicit trace/debug level for a focused
diagnostic.

## Direct-read recovery and POST boundary

Direct idempotent reads preserve the old engine's bounded reconnect behavior:
`GROUP`, `XOVER`, `XHDR`, `HEAD`, `BODY`, `ARTICLE`, and `NOOP` run through one
central retry wrapper. After a classified transport failure, the transport
disconnects, reconnects, reselects the previous group, backs off briefly, and
retries within the fixed retry budget. Terminal `430 no such article` is not
retried.

Bulk ARTICLE pipelining emits one sanitized operational failure event when a
pipeline attempt fails, including failures during the initial connect/auth/TLS
phase before any request is sent. The event records role, group, operation,
window, requested/terminal/unresolved/in-flight/pending counts, error class,
code, and timing. It does not include message IDs, credentials, AUTH commands,
wire commands, article bodies, NZB/image payloads, or posted content.

`POST` is deliberately outside that idempotent retry wrapper. Once the server
has accepted message data, retrying can create a duplicate post. POST failures
are logged centrally with sanitized context, but the caller receives the error
without automatic replay.

## Verification status

- Fixture-tested: central transport `GROUP`, `XOVER`, `XHDR`, `HEAD`, `BODY`,
  single `ARTICLE`, pipelined `ARTICLE`, direct `HEAD`/`BODY`/`ARTICLE`
  disconnect-then-reconnect success, terminal `430` no-retry, exact `POST`
  header/body separator framing and dot-stuffing, disconnect/protocol recovery,
  initial pipeline connect failure conversion/logging, pipeline ARTICLE failure
  operational logging, sanitized diagnostic context, and static architecture
  guards.
- Scheduled retriever tests: comments/spots/reports share the central
  transport and recovery paths covered by the existing pipelined retriever
  tests. Spots scheduled full spot retrieval remains functionally one-by-one
  inside the legacy orchestration, but the NNTP transport under it is central.
- Manual live integration validation covered scheduled delta retrieval,
  full-spot reads, image reads, NZB reads and hand-off, plus repeated
  cursor/lock checks. These checks complement the deterministic fixtures; they
  are not a substitute for independent maintainer and provider testing.
- Not live-tested: posting. Live NNTP POST is intentionally forbidden for this
  branch; only deterministic fixture POST framing is tested, and POST is not
  auto-retried because it is non-idempotent.

## Safe test commands

Run PHP lint/tests only through Docker because the host has no PHP:

```sh
docker run --rm \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint sh spotweb:server01-fixes-20260816-r2 \
  -lc 'find lib/services/Nntp lib/services/Retriever lib/services/Posting lib/services/Providers lib/services/Actions lib/page bin tests/Services/Nntp tests/Support -name "*.php" -print | sort | xargs -r -n1 php -l'

docker run --rm \
  -v /tmp/phpunit-11.phar:/tmp/phpunit.phar:ro \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint php spotweb:server01-fixes-20260816-r2 \
  /tmp/phpunit.phar --do-not-cache-result --bootstrap vendor/autoload.php tests
```

## Rollback and deployment plan

Production switch must be one controlled image/compose deployment after tests
pass and the branch is reviewed. Do not run against live MySQL/cursors during
review. Rollback is the previous production image/compose plus existing
database/config state; this branch does not require schema changes beyond the
already-created pipeline-depth setting.

Before deployment, run the read-only live smoke commands from
`docs/pipelined-comments-retriever.md`, then a short production shadow/read-only
receive check. Do not test live posting; validate posting only with fixture
tests until a deliberate manual posting test is approved.
