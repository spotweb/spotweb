Third-party notices
===================

NNTP pipeline provenance
------------------------

This private branch does **not** vendor, copy, or modify source code from
`robinvdvleuten/php-nntp` / `rvdv/nntp`.

That package was evaluated during design as a modern PHP NNTP reference, but
the implementation committed here is original Spotweb code using PHP streams
directly:

- `Services_Nntp_PipelinedTransport`
- `Services_Nntp_PipelinedRecovery`
- related result/outcome/exception classes

Because no third-party NNTP source is bundled, there is no separate NNTP
third-party licence text to retain in this repository for this feature.

Reference evaluated, not used as source:

- https://github.com/robinvdvleuten/php-nntp
- evaluated commit: `d5c59c90f02a82ca09609f9a6e912f9d78f0faeb`
