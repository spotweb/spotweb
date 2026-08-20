rvdv/nntp internal pin
======================

Upstream: https://github.com/robinvdvleuten/php-nntp
Pinned commit: d5c59c90f02a82ca09609f9a6e912f9d78f0faeb
Pinned commit date: 2026-07-28T11:06:27+00:00
Licence: MIT
Copyright: Robin van der Vleuten <robin@webstronauts.com>

This private Spotweb branch keeps the dependency internal while the comments
retriever performance work is tested. The upstream public API remains blocking;
Spotweb's adapter/transport layer adds non-blocking pipelined ARTICLE support
for scheduled comment retrieval only.
