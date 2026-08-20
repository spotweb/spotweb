# Pipelined comments benchmark evidence — 2026-08-20

These reports are sanitized read-only benchmark outputs for the scheduled
comments stream only. They contain article ranges, timing, counts, and
connection counts only; no NNTP host, username, or password fields are present.
The benchmark utility is reusable for spots/comments/reports, but no equivalent
live spots or reports measurements are claimed here.

Primary source reports:

- `/tmp/spotweb-pipeline-live-random-sweep-20260820-151120.jsonl`
- `/tmp/spotweb-pipeline-live-random-replacements-20260820-154502.jsonl`

Methodology:

- read-only full-comment retrieval path;
- one NNTP text/header connection;
- 1,000 headers/articles per sample;
- randomized ranges to avoid same-range/cache bias;
- randomized window order in sweep mode;
- replacement samples were used to bring each tested window to 10 successful
  samples where possible.

Decision-grade randomized result, 10 successful samples/window:

| Window | Median/s | Mean/s | Min/s | Max/s | Command-level failures |
| ---: | ---: | ---: | ---: | ---: | ---: |
| 8 | 44.98 | 41.77 | 30.01 | 50.89 | 3 |
| 16 | 44.76 | 39.79 | 22.15 | 48.75 | 0 |
| 32 | 46.77 | 43.56 | 26.77 | 51.06 | 0 |
| 64 | 46.23 | 43.99 | 29.53 | 47.08 | 1 |
| 128 | 41.95 | 41.97 | 22.01 | 53.34 | 2 |

Interpretation:

- Window 32 is the selected default. It had the strongest median in this
  decision set and zero command-level failures.
- The benchmark command-level failures were outer 180-second sample failures.
  They do not identify the number of unresolved individual `ARTICLE` requests.
- Earlier same-range 64/128/256 experiments are cache-sensitive preliminary
  data only and are not used as the configuration decision.

## Raw JSONL: random sweep

```jsonl
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21352587, "requested_last": 21353586, "offset_from_latest": 673097, "headers": 1000, "processed": 1000, "seconds": 33.866, "items_per_second": 29.53, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 4}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21657594, "requested_last": 21658593, "offset_from_latest": 368090, "headers": 1000, "processed": 1000, "seconds": 27.989, "items_per_second": 35.73, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 7}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21255250, "requested_last": 21256249, "offset_from_latest": 770435, "headers": 1000, "processed": 1000, "seconds": 22.721, "items_per_second": 44.01, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 3}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21972894, "requested_last": 21973893, "offset_from_latest": 52791, "headers": 1000, "processed": 1000, "seconds": 41.608, "items_per_second": 24.03, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 6}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21063247, "requested_last": 21064246, "offset_from_latest": 962441, "headers": 1000, "processed": 1000, "seconds": 21.299, "items_per_second": 46.95, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 10}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21375966, "requested_last": 21376965, "offset_from_latest": 649722, "headers": 1000, "processed": 1000, "seconds": 24.386, "items_per_second": 41.01, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 3}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21273854, "requested_last": 21274853, "offset_from_latest": 751836, "headers": 1000, "processed": 1000, "seconds": 24.479, "items_per_second": 40.85, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 5}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21542168, "requested_last": 21543167, "offset_from_latest": 483523, "headers": 1000, "processed": 1000, "seconds": 21.972, "items_per_second": 45.51, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 7}
{"mode":"read-only","window":8,"sample":8,"offset_from_latest":100758,"error":"benchmark command failed or timed out"}
{"mode":"read-only","window":8,"sample":3,"offset_from_latest":303008,"error":"benchmark command failed or timed out"}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21919596, "requested_last": 21920595, "offset_from_latest": 106101, "headers": 1000, "processed": 1000, "seconds": 45.152, "items_per_second": 22.15, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 10}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21577074, "requested_last": 21578073, "offset_from_latest": 448625, "headers": 1000, "processed": 1000, "seconds": 31.616, "items_per_second": 31.63, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 4}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21498258, "requested_last": 21499257, "offset_from_latest": 527441, "headers": 1000, "processed": 1000, "seconds": 24.402, "items_per_second": 40.98, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 3}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21570094, "requested_last": 21571093, "offset_from_latest": 455605, "headers": 1000, "processed": 1000, "seconds": 20.615, "items_per_second": 48.51, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 10}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21693256, "requested_last": 21694255, "offset_from_latest": 332443, "headers": 1000, "processed": 1000, "seconds": 19.649, "items_per_second": 50.89, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 5}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21357502, "requested_last": 21358501, "offset_from_latest": 668197, "headers": 1000, "processed": 1000, "seconds": 21.767, "items_per_second": 45.94, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 8}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21954838, "requested_last": 21955837, "offset_from_latest": 70863, "headers": 1000, "processed": 1000, "seconds": 37.352, "items_per_second": 26.77, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 8}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21027649, "requested_last": 21028648, "offset_from_latest": 998052, "headers": 1000, "processed": 1000, "seconds": 18.746, "items_per_second": 53.34, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 4}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21648593, "requested_last": 21649592, "offset_from_latest": 377110, "headers": 1000, "processed": 1000, "seconds": 21.472, "items_per_second": 46.57, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 9}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21241548, "requested_last": 21242547, "offset_from_latest": 784155, "headers": 1000, "processed": 1000, "seconds": 21.24, "items_per_second": 47.08, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 1}
{"mode":"read-only","window":128,"sample":10,"offset_from_latest":802704,"error":"benchmark command failed or timed out"}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21629831, "requested_last": 21630830, "offset_from_latest": 395875, "headers": 1000, "processed": 1000, "seconds": 24.315, "items_per_second": 41.13, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 9}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21747267, "requested_last": 21748266, "offset_from_latest": 278439, "headers": 1000, "processed": 1000, "seconds": 21.846, "items_per_second": 45.78, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 9}
{"mode":"read-only","window":8,"sample":1,"offset_from_latest":675777,"error":"benchmark command failed or timed out"}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21103351, "requested_last": 21104350, "offset_from_latest": 922358, "headers": 1000, "processed": 1000, "seconds": 21.436, "items_per_second": 46.65, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 6}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21708756, "requested_last": 21709755, "offset_from_latest": 316954, "headers": 1000, "processed": 1000, "seconds": 21.622, "items_per_second": 46.25, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 5}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21408048, "requested_last": 21409047, "offset_from_latest": 617663, "headers": 1000, "processed": 1000, "seconds": 29.194, "items_per_second": 34.25, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 4}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21156943, "requested_last": 21157942, "offset_from_latest": 868768, "headers": 1000, "processed": 1000, "seconds": 21.791, "items_per_second": 45.89, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 2}
{"mode":"read-only","window":64,"sample":3,"offset_from_latest":817369,"error":"benchmark command failed or timed out"}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21040882, "requested_last": 21041881, "offset_from_latest": 984833, "headers": 1000, "processed": 1000, "seconds": 25.313, "items_per_second": 39.51, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 4}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21124253, "requested_last": 21125252, "offset_from_latest": 901464, "headers": 1000, "processed": 1000, "seconds": 27.342, "items_per_second": 36.57, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 7}
{"mode":"read-only","window":128,"sample":6,"offset_from_latest":966546,"error":"benchmark command failed or timed out"}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21453620, "requested_last": 21454619, "offset_from_latest": 572099, "headers": 1000, "processed": 1000, "seconds": 22.551, "items_per_second": 44.34, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 8}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21812907, "requested_last": 21813906, "offset_from_latest": 212815, "headers": 1000, "processed": 1000, "seconds": 20.515, "items_per_second": 48.75, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21788097, "requested_last": 21789096, "offset_from_latest": 237626, "headers": 1000, "processed": 1000, "seconds": 19.585, "items_per_second": 51.06, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 9}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21431790, "requested_last": 21432789, "offset_from_latest": 593933, "headers": 1000, "processed": 1000, "seconds": 23.829, "items_per_second": 41.97, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 7}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21135322, "requested_last": 21136321, "offset_from_latest": 890405, "headers": 1000, "processed": 1000, "seconds": 21.442, "items_per_second": 46.64, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 7}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21054554, "requested_last": 21055553, "offset_from_latest": 971173, "headers": 1000, "processed": 1000, "seconds": 21.571, "items_per_second": 46.36, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 1}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21639480, "requested_last": 21640479, "offset_from_latest": 386248, "headers": 1000, "processed": 1000, "seconds": 23.618, "items_per_second": 42.34, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 8}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21815261, "requested_last": 21816260, "offset_from_latest": 210467, "headers": 1000, "processed": 1000, "seconds": 26.27, "items_per_second": 38.07, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 10}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21696663, "requested_last": 21697662, "offset_from_latest": 329065, "headers": 1000, "processed": 1000, "seconds": 19.705, "items_per_second": 50.75, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 16, "requested_first": 21145262, "requested_last": 21146261, "offset_from_latest": 880466, "headers": 1000, "processed": 1000, "seconds": 21.845, "items_per_second": 45.78, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 5}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21620931, "requested_last": 21621930, "offset_from_latest": 404797, "headers": 1000, "processed": 1000, "seconds": 23.246, "items_per_second": 43.02, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 5}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21360966, "requested_last": 21361965, "offset_from_latest": 664762, "headers": 1000, "processed": 1000, "seconds": 23.858, "items_per_second": 41.92, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 1}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21075178, "requested_last": 21076177, "offset_from_latest": 950550, "headers": 1000, "processed": 1000, "seconds": 27.285, "items_per_second": 36.65, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 9}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21946159, "requested_last": 21947158, "offset_from_latest": 79570, "headers": 1000, "processed": 1000, "seconds": 45.436, "items_per_second": 22.01, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21032764, "requested_last": 21033763, "offset_from_latest": 992965, "headers": 1000, "processed": 1000, "seconds": 20.061, "items_per_second": 49.85, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21782291, "requested_last": 21783290, "offset_from_latest": 243438, "headers": 1000, "processed": 1000, "seconds": 33.323, "items_per_second": 30.01, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 6}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21118942, "requested_last": 21119941, "offset_from_latest": 906789, "headers": 1000, "processed": 1000, "seconds": 21.147, "items_per_second": 47.29, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 1}
{"mode": "pipelined-read-only-full-comment-path", "window": 32, "requested_first": 21543429, "requested_last": 21544428, "offset_from_latest": 482302, "headers": 1000, "processed": 1000, "seconds": 20.898, "items_per_second": 47.85, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "sample": 6}
```

## Raw JSONL: replacement samples

```jsonl
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21241883, "requested_last": 21242882, "offset_from_latest": 783849, "headers": 1000, "processed": 1000, "seconds": 21.898, "items_per_second": 45.67, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 3}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21420877, "requested_last": 21421876, "offset_from_latest": 604856, "headers": 1000, "processed": 1000, "seconds": 21.056, "items_per_second": 47.49, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 64, "requested_first": 21722234, "requested_last": 21723233, "offset_from_latest": 303499, "headers": 1000, "processed": 1000, "seconds": 22.097, "items_per_second": 45.26, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 1}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21525428, "requested_last": 21526427, "offset_from_latest": 500306, "headers": 1000, "processed": 1000, "seconds": 21.858, "items_per_second": 45.75, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 2}
{"mode": "pipelined-read-only-full-comment-path", "window": 8, "requested_first": 21773525, "requested_last": 21774524, "offset_from_latest": 252210, "headers": 1000, "processed": 1000, "seconds": 22.573, "items_per_second": 44.3, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 1}
{"mode": "pipelined-read-only-full-comment-path", "window": 128, "requested_first": 21839105, "requested_last": 21840104, "offset_from_latest": 186631, "headers": 1000, "processed": 1000, "seconds": 19.937, "items_per_second": 50.16, "article_statuses": 1000, "fullcomments": 1000, "connections": 1, "replacement_sample": 1}
```
