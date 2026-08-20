Third-party notices
===================

rvdv/nntp
---------

Spotweb's private pipelined comments retriever uses an internally maintained
NNTP transport based on the design and protocol vocabulary of:

- Project: https://github.com/robinvdvleuten/php-nntp
- Package: rvdv/nntp
- Pinned upstream commit: d5c59c90f02a82ca09609f9a6e912f9d78f0faeb
- Copyright: Robin van der Vleuten <robin@webstronauts.com>
- Licence: MIT

The upstream package is blocking. Spotweb's private integration adds its own
non-blocking stream and response FIFO support for the scheduled comments
retriever while preserving the upstream attribution and licence.

The full MIT licence text is retained in lib/thirdparty/rvdv-nntp/LICENSE.
