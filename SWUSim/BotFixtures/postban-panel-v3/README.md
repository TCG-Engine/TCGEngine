# Post-ban test panel v3 (frozen 2026-11-21)

This is the same panel as `postban-panel-v2/` except slot **p4**. On 2026-11-21 the owner asked for p4 to be updated to Ninin's current Boba Blue Burn list. v1 and v2 stay as they were, so runs on them keep their baselines; the v2 targeted `indirectbase` run (`ib2-*`) is one of those. Compare an arm only with runs on the same panel version (`panel=` in each run's `meta.txt`).

| slot | change from v2 |
|---|---|
| p1–p3, p5–p8 | unchanged (only the first comment line) |
| p4 Boba Blue Burn (JTL_009 / JTL_020) | now the current SWU Forge export `cmwa78xln1sbg01pleo1fy72d`, = as-played `ace46c2a`, first played 2026-11-21. It went 5-0 in its first 5 games. |

**p4 changes, v2 → v3:**
- **Main deck:** +1 IG-2000 JTL_140 (now 3), +1 HK-47 LOF_130 (now 3), −1 TIE Bomber JTL_237 (now 2), −1 Fett's Firespray JTL_240 (now 2). Still 51 cards.
- **Sideboard:** +1 TIE Bomber JTL_237, −1 HK-47 LOF_130. Still 10 cards.

Every card ID was already in the v2 list, so all are known to the engine and none are banned. For roles, sources and the other rulings, see `../postban-panel-v1/README.md`; for the v1 → v2 change, see `../postban-panel-v2/README.md`.

**Run a screen on v3:** use the same harness with `PANEL=SWUSim/BotFixtures/postban-panel-v3`. The default is still v1.

```
docker exec -d -w /var/www/html/TCGEngine -e PANEL=SWUSim/BotFixtures/postban-panel-v3 otmtcge-swusim-web-server-1 \
  bash SWUSim/DevTools/postban-panel/run-panel.sh <run-name> <our fixture> <our style> "<control variant>" "<on variant>"
```

**Targeted run** (only the slots where the lever fires, with more seeds): add `OPPS="p4"` (space-separated slot prefixes) and `SEEDS=300`. A run with `OPPS` set plays no mirror.

**Not for the Arenabot picker:** like v1 and v2, this folder is not in `SWU_PRECON_GROUPS`.
