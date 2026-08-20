# Private pipelined comments retriever

This branch is private development work for the scheduled Spotweb comments
retriever. Do not open an upstream pull request for this feature until it has
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

Only `Services_Retriever_Comments::perform()` is routed to the new flow. The
legacy PEAR-derived NNTP engine remains in place for spots, reports, posting,
page reads, NZB/image access, and every non-comment path.

The new comments flow uses:

- `Services_Nntp_PipelinedTransport`: one reusable NNTP stream with
  plain/implicit TLS/STARTTLS, auth, `GROUP`, `XOVER`, `XHDR Message-ID`, and a
  bounded FIFO `ARTICLE` pipeline.
- `Services_Retriever_CommentsPipelined`: comments-specific orchestration,
  retention/duplicate logic, parsing, DAO writes, and cursor checkpoint.
- `Services_Retriever_CommentsDaoSink`: production-compatible DAO writes.
- `Services_Retriever_CommentsCaptureSink`: DB-free capture/parity sink.

The internal pipeline window defaults to 16. It is not exposed as a user
setting. If a pipelined batch fails with a window greater than 1, the current
run falls back to window 1 and re-fetches the batch before any DAO/cursor
commit.

## Attribution

The transport is an internal Spotweb adapter for the private rvdv/nntp-based
work. Upstream reference:

- https://github.com/robinvdvleuten/php-nntp
- pinned commit `d5c59c90f02a82ca09609f9a6e912f9d78f0faeb`
- MIT licence, retained in `lib/thirdparty/rvdv-nntp/LICENSE`

See `THIRD_PARTY_NOTICES.md`.

## Validation commands

Host PHP is not installed on `server01`; these commands use the existing
Spotweb image only as a disposable PHP runtime with this checkout mounted.

Syntax check:

```sh
docker run --rm \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint sh spotweb:server01-fixes-20260816-r2 \
  -lc 'for f in lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php lib/services/Nntp/Services_Nntp_PipelinedTransport.php lib/services/Retriever/Services_Retriever_CommentsArticleParser.php lib/services/Retriever/Services_Retriever_CommentsSink.php lib/services/Retriever/Services_Retriever_CommentsDaoSink.php lib/services/Retriever/Services_Retriever_CommentsCaptureSink.php lib/services/Retriever/Services_Retriever_CommentsPipelined.php lib/services/Retriever/Services_Retriever_Comments.php utils/pipelined_comments_capture.php utils/pipelined_comments_synthetic_benchmark.php tests/Services/Nntp/ServicesNntpPipelinedTransportTest.php tests/Services/Retriever/ServicesRetrieverCommentsPipelinedTest.php; do php -l "$f" || exit 1; done'
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

Synthetic benchmark, no provider and no DB:

```sh
docker run --rm \
  -v /home/bschlepe/codex_work/spotweb-pipelined-comments:/work:ro \
  -w /work \
  --entrypoint php spotweb:server01-fixes-20260816-r2 \
  utils/pipelined_comments_synthetic_benchmark.php 1000
```

## Real-provider gate

Do not run provider benchmarks while the production comments catch-up is active.
After the catch-up finishes, use a read-only range and compare window 1, 4, 8,
16, and 32 with:

```sh
php utils/pipelined_comments_capture.php --use-bootstrap-settings --group <comment-group> --first <first> --last <last> --window <n>
```

That launcher reads `nntp_hdr` through normal Bootstrap/settings, keeps the
credentials in memory, and emits only counts/status/canonical capture output.

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
