# Private scheduled retriever pipelining

This branch is private development work for the scheduled Spotweb bulk
retrievers. Do not open an upstream pull request for this feature until it has
been tested against a real provider and the remaining risks have been closed.

## Integration base

The feature branch is based on `integration/pipelined-comments-base`, created
from latest `spotweb/spotweb:develop` and then stacked with:

1. `f6665ffa212f5ede3e4279e57de41bbf420229a5` — normalize comment From headers.
2. `3d8e3b5122fd1dbc42490c956006a82645f2b306` — MySQL InnoDB/utf8mb4 schema.
3. `1762c38c0ca74396a7b3c65ba2d21124e802f787` — retrieve advisory lock.
4. `27a70b2bb6e3b846a6123301c8ff8ee02fd61c9d` — widen `spotsfull` signature fields.

The private integration base resolves the #991/#993 schema collision by moving
the integrated schema version to `0.71`, leaving the existing upstream PR
branches unchanged.

## Scope

Scheduled `Services_Retriever_Spots`, `Services_Retriever_Comments`, and
`Services_Retriever_Reports` share the modern text NNTP transport below
`Services_Retriever_Base` for `GROUP`, cursor recovery (`XHDR Message-ID`), and
the normal 1,000-header `XOVER` loop.

The later internal NNTP migration phase supersedes the original
scheduled-only boundary: request-driven full spot/comment/NZB/image reads,
cache checking, install/config tests, and posting adapters now also use the
same central internal NNTP transport. See
`docs/internal-nntp-migration.md` for the current architecture gate and
deployment/test matrix.

Scheduled ARTICLE handling remains stream-specific:

- Scheduled comments use shared bulk ARTICLE recovery for the full-comment
  body path.
- Scheduled spots use the same shared recovery and parser model for full-spot
  text ARTICLE retrieval, but the current legacy spots flow still calls it
  one-by-one via `readFullSpotPipelined()` because image/NZB prefetch and
  fullspot persistence are interleaved with per-spot processing.
- Scheduled reports have no separate full ARTICLE body path; their scheduled
  retrieval uses the shared Base `GROUP`/`XHDR`/`XOVER` text transport.

The scheduled bulk flow uses:

- `Services_Nntp_PipelinedTransport`: one reusable NNTP stream with
  plain/implicit TLS/STARTTLS, auth, `GROUP`, `XOVER`, `XHDR Message-ID`, and a
  bounded FIFO `ARTICLE` pipeline.
- `Services_Nntp_PipelinedRecovery`: shared FIFO-aware recovery and retry
  policy for terminal/unresolved `ARTICLE` outcomes.
- `Services_Retriever_PipelinedArticleBatch`: content-independent ARTICLE
  outcome application and contiguous-prefix selection.
- Stream-specific scheduled code supplies only group/cursor adapters,
  parser/validator behavior, and DAO persistence/linking rules.
- `Services_Retriever_CommentsCaptureSink`: DB-free capture/parity sink for
  comments fixtures and benchmarks.

The administrator-selected per-NNTP-server article pipeline depth defaults to
32 and is bounded to 1-128. Depth 1 disables pipelining. Recovery first retries
only unresolved IDs at the configured depth, then retries remaining unresolved
IDs at window 1. Terminal completed responses and terminal `430` results are
not repeated merely because a later FIFO request failed.

## Provenance

The transport is original internal Spotweb code. The branch previously cited
`robinvdvleuten/php-nntp` as if it were vendored/adapted, but audit showed the
implementation is a raw PHP stream transport and does not import that package.
The docs/notices now reflect that honestly.

The evaluated reference remains recorded only as design context:

- https://github.com/robinvdvleuten/php-nntp
- evaluated commit `d5c59c90f02a82ca09609f9a6e912f9d78f0faeb`

No third-party NNTP source is bundled. See `THIRD_PARTY_NOTICES.md`.

## Validation commands

Host PHP is not installed on `server01`; these commands use the existing
Spotweb image only as a disposable PHP runtime with this checkout mounted.

Syntax check:

```sh
docker run --rm \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint sh spotweb:server01-fixes-20260816-r2 \
  -lc 'for f in lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php lib/services/Nntp/Services_Nntp_PipelinedTransport.php lib/services/Retriever/Services_Retriever_PipelinedArticleBatch.php lib/services/Retriever/Services_Retriever_CommentsArticleParser.php lib/services/Retriever/Services_Retriever_SpotsArticleParser.php lib/services/Retriever/Services_Retriever_Comments.php utils/pipelined_comments_benchmark.php utils/pipelined_comments_synthetic_benchmark.php tests/Services/Nntp/ServicesNntpPipelinedTransportTest.php tests/Services/Nntp/ServicesNntpPipelinedRecoveryTest.php tests/Services/Retriever/ServicesRetrieverPipelinedArticleBatchTest.php tests/Services/Retriever/ServicesRetrieverCommentsPipelinedTest.php; do php -l "$f" || exit 1; done'
```

Focused tests:

```sh
docker run --rm \
  -v /tmp/phpunit-11.phar:/tmp/phpunit.phar:ro \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint php spotweb:server01-fixes-20260816-r2 \
  /tmp/phpunit.phar --do-not-cache-result --bootstrap vendor/autoload.php \
  tests/Services/Nntp/ServicesNntpPipelinedTransportTest.php \
  tests/Services/Retriever/ServicesRetrieverCommentsPipelinedTest.php
```

Fixture benchmark, no provider and no DB:

```sh
docker run --rm \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint php spotweb:server01-fixes-20260816-r2 \
  utils/pipelined_comments_benchmark.php --read-only --fixture --group free.pt \
  --first 100 --count 1000 --windows 1,4,8,16,32 --samples 2 --sweep --seed 123
```

Config discovery check for a read-only mounted Spotweb root:

```sh
docker run --rm \
  --entrypoint php \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -v <host-spotweb-root-containing-dbsettings-and-settings>:/var/www/spotweb:ro \
  -w /work \
  spotweb:server01-fixes-20260816-r2 \
  utils/pipelined_comments_benchmark.php --read-only --config-discovery-check \
  --spotweb-root /var/www/spotweb
```

## Real-provider gate

Do not run provider benchmarks while the production comments catch-up is active.
After the catch-up finishes, mount the production Spotweb root read-only and
use a read-only range. The checkout remains mounted at `/work`; configuration
is discovered from `--spotweb-root`, so `dbsettings.inc.php` is not copied or
written into `/work`.

```sh
docker run --rm \
  --entrypoint php \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -v <host-spotweb-root-containing-dbsettings-and-settings>:/var/www/spotweb:ro \
  -w /work \
  spotweb:server01-fixes-20260816-r2 \
  utils/pipelined_comments_benchmark.php --read-only --use-bootstrap-settings \
  --spotweb-root /var/www/spotweb \
  --group <comment-group> --first <first> --count 1000 \
  --windows 1,8,16,32,64,128 --samples 10 --sweep --random-range <first-last> \
  --seed <seed> --jsonl /tmp/spotweb-pipeline-live.jsonl
```

That launcher reads `nntp_hdr` through normal Spotweb DB/file settings loaded
from the explicit read-only root, keeps credentials in memory, and emits only
JSONL counts/status/timing/error data. It bypasses the image entrypoint and
mounts configuration read-only.

Safe live smoke checks have been performed for transport receive/drop-in only:
spots group `free.pt` and reports group `free.willey` each returned 10 XOVER
headers, 10 terminal ARTICLE results, 0 unresolved, and 0 retries/errors. These
checks do not prove full parser/DAO integration performance.

The 2026-08-20 sanitized benchmark evidence and selected default are documented
in `docs/benchmarks/pipelined-comments-20260820.md`.

## Remaining risks before deployment

- Real-provider compatibility is untested by design in this branch.
- The capture tests currently prove transport FIFO behaviour, 430 handling,
  duplicate/cursor commit boundaries, and deterministic window parity on
  synthetic data. A disposable-DB row-level parity run is still required before
  production deployment.
- STARTTLS is mapped and guarded but needs real/provider or TLS-fixture
  validation.
- Production rollout must wait until the current historic comments catch-up has
  completed.
