# Scheduled retriever pipelining — follow-up implementation tasks

These items track hardening work for the private scheduled retriever pipeline.
The implementation now targets scheduled spots, comments and reports below
`Services_Retriever_Base`; request-driven reads and posting remain out of
scope for this iteration.

Current limitation: comments use bulk ARTICLE recovery; spots use the shared
recovery layer one full spot at a time from the legacy spots flow; reports do
not have a full ARTICLE body path in scheduled retrieval.

## 1. Failure hardening: retry only unresolved articles

The shared recovery layer must reconnect and repeat only unresolved `ARTICLE`
work after a pipeline failure.

- The transport must expose completed terminal results and the ordered set of
  unresolved message IDs when a connection/read/write/timeout/protocol failure
  occurs.
- A result is terminal when a complete NNTP response has been read:
  successful article, or expected `430 no such article`. Preserve the existing
  behavior for malformed/unparseable comment payloads: they are terminal and
  non-fatal.
- An interrupted article body and every request still pending behind it are
  unresolved. Reconnect and retry only those IDs, preserving their order.
- A failed final article may be carried into the next 1,000-header work batch;
  do not require a separate immediate retry merely because it was last in the
  current batch. The same applies to unresolved IDs once the bounded retry
  budget is exhausted.
- The cursor may advance only through a contiguous sequence of terminal
  outcomes. Never let a later successful article move the cursor past an
  unresolved earlier article.
- Persist/commit a batch only after it has a safe contiguous terminal prefix.
  Inserts must remain idempotent so a process crash between data insert and
  cursor persistence cannot create a functional duplicate or a skipped item.
- Use a small fixed retry budget. First reconnect and retry unresolved IDs at
  the configured pipeline depth; after that, retry unresolved IDs at window 1.
  If recovery still fails, leave the unresolved tail for the next scheduled
  run, log it clearly, and exit non-successfully.
- Log: article range, configured window, active window, completed count,
  unresolved count, retry attempt, NNTP error class/code, and whether work was
  deferred to the next batch/run.
- Add deterministic fixture tests for disconnect before response, disconnect
  during a multiline body, timeout, 430, malformed payload, failure of final
  article, and process restart/cursor continuity.

## 2. Ship the benchmark as a Spotweb utility

Move the read-only benchmark from `/tmp` into the Spotweb repository as a
supported developer/administrator utility.

- It must use normal Spotweb configuration loading; credentials are never
  command-line arguments or printed in output.
- It must never create/update database rows, cursor state, configuration, or
  cache. Provide an explicit `--read-only` guard and make it the only mode in
  the first version.
- Support `--window`, `--count`, `--samples`, `--random-range`, `--seed`, and
  machine-readable JSON Lines output. Record requested article range, actual
  header count, completed/terminal/unresolved counts, elapsed time, rate,
  connection count, retries, and errors.
- A sweep mode must randomize execution order across requested windows so
  changing provider load does not systematically favour one depth. It must
  retain failures in the report and report them separately from successful
  samples.
- Add a Docker invocation to the documentation that bypasses the image
  entrypoint and mounts production configuration read-only. Do not use it to
  alter production state.
- Add fixture-based tests so the tool can be validated without a real NNTP
  provider or database.

## 3. Fixed NNTP pipeline-depth setting

Pipeline depth is a fixed administrator-selected setting, never dynamically
adapted during a run.

- Add a per-NNTP-server setting named clearly, e.g. `nntp_article_pipeline_depth`.
- Default to a conservative compatible value (decide after the randomized
  benchmark); validate an integer range with a safe hard maximum. `1` disables
  pipelining.
- Add the setting through Spotweb's normal schema/upgrade path, including an
  `upgrade-db` migration. No manual SQL step.
- Add the corresponding NNTP server configuration form field, validation,
  explanatory help text, read/display logic, and tests.
- The scheduled bulk retrievers read this setting for their configured window. No
  automatic tuning and no hidden per-run changes except the failure-recovery
  fallback described above.
- Preserve existing installations: absent setting must resolve to the default
  after upgrade, and existing NNTP server records must remain valid.

## Acceptance gate

Do not deploy the pipeline as the production retriever until the randomized
benchmark is complete, parity/failure tests pass, retry/cursor behavior is
audited, and the chosen fixed depth has a recorded rationale.
