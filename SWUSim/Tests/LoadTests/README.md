# Load Tests

A lightweight [k6](https://k6.io/) runner for SWU Stats HTTP endpoints.

## Install k6

```sh
brew install k6
```

## Run

Defaults are deliberately gentle (50 rps for 10s against a local dev instance):

```sh
k6 run SWUSim/Tests/LoadTests/loadtest.js
```

Override any knob with `-e`:

```sh
# 1000 rps for 10s against a chosen endpoint
k6 run -e BASE_URL=https://swustats.net -e PATH=/TCGEngine/Stats/APIs.php \
       -e RATE=1000 -e DURATION=10s SWUSim/Tests/LoadTests/loadtest.js
```

| Env | Meaning | Default |
| --- | --- | --- |
| `BASE_URL` | origin to hit | `http://localhost:3100` |
| `PATH` | path under `BASE_URL` | `/TCGEngine/Stats/APIs.php` (static docs page) |
| `RATE` | requests per second | `50` |
| `DURATION` | how long to sustain `RATE` | `10s` |
| `VUS` | preallocated virtual users | `200` |
| `MAX_VUS` | virtual-user ceiling | `1000` |

## Do not load-test the melee proxy

`APIs/MeleeLinkToJson.php` (and anything that proxies to melee.gg) is **uncached** — each
request makes one live fetch to melee.gg. Pointing load at it is a denial-of-service
against a third party and will likely get the server IP blocked there, breaking imports
for everyone. Only target your own cacheable endpoints.

## Before hitting live prod hard

`swustats.net` is production. A sustained 1000 rps is a capacity test, not a smoke test:
coordinate it, run off-peak, and **ramp up** — start low, watch the error rate and p95,
then increase. The script's thresholds fail the run above 1% errors or p95 > 2s so a run
that is hurting the site shows red immediately.
