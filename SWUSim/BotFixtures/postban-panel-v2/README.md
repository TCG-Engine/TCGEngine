# Post-ban test panel v2 (frozen 2026-11-20)

This is the same panel as `postban-panel-v1/` except slot **p4**. On 2026-11-20 the owner said "yes" to refreshing it to Ninin's current Boba Blue Burn list. v1 stays as it was, so the screens already run on v1 keep their baselines. Compare an arm only with runs on the same panel version (`panel=` in each run's `meta.txt`).

| slot | change from v1 |
|---|---|
| p1–p3, p5–p8 | unchanged (only the first comment line) |
| p4 Boba Blue Burn (JTL_009 / JTL_020) | now the SWU Forge export `cmwa78xln1sbg01pleo1fy72d` "main / v1", the list Ninin played 30 games with from 2026-11-17 to 2026-11-21 (as-played `3a2b1058`). |

**p4 changes, v1 → v2:**
- **Added:** Ninth Sister ASH_148 ×2; +1 each of Rancor Keeper ASH_032, TIE Bomber JTL_237, Fett's Firespray JTL_240, Punishing One SEC_171 (now 3 each).
- **Removed:** Justifier ASH_146 ×2, Mining Guild Trespasser HMW_186 ×2, 0-0-0 LAW_174 ×2.
- **Sideboard:** now ASH_048 ×3, JTL_181, LAW_191 ×3, LOF_130, SEC_163 ×2.

Every card ID is known to the engine, and there are no banned cards. For roles, sources and the other rulings, see `../postban-panel-v1/README.md`.

**Run a screen on v2:** the same harness with `PANEL=SWUSim/BotFixtures/postban-panel-v2` (the default stays v1):

```
docker exec -d -w /var/www/html/TCGEngine -e PANEL=SWUSim/BotFixtures/postban-panel-v2 otmtcge-swusim-web-server-1 \
  bash SWUSim/DevTools/postban-panel/run-panel.sh <run-name> <our fixture> <our style> "<control variant>" "<on variant>"
```

**Targeted run** (only the slots where the lever fires, more seeds): add `OPPS="p4"` (space-separated slot prefixes) and `SEEDS=300`. A run with `OPPS` set plays no mirror.

**Not for the Arenabot picker:** like v1, this folder is not in `SWU_PRECON_GROUPS`.
