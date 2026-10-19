# Optional bot decisions

`BotLegalActions.php` offers every live single-card choice, including encoded opportunity
abilities, plus PASS for optional choices. YESNO offers both answers. Multichoice retains
its full candidate pool and bounds in the decision context; the strategy builds a bounded
subset instead of enumerating exponentially many combinations.

`BotDecisions.php` scores known choices with the acting seat's perspective and restores the
caller's perspective afterward. Policies currently cover:

- Affordable champion progression and ordinary materialization, preferring guns over ammunition.
- Graveyard floating-memory payments, capped at the amount owed, preserving field permanents.
- Starter bullet loading and optional weapon use without extra reserve taxes.
- Evasive Maneuvers when two prevention changes lethal incoming combat damage into survival,
  with a matching friendly-target policy and guards against duplicate spending.
- Umbra Sight's optional memory draw, accounting for curse damage and a life/deck margin.
- Free Recover 2/3/5 prompts, floating-memory-to-draw, and Diana's Aethercharge load sequence.

This is a conservative heuristic, not a complete combat simulator. Other fast actions,
unknown optional sacrifices/discards and special materialization costs still pass. Unsupported
optional policies are reported through `GABotUnsupportedOptionalDecisions` and the server log,
including source card IDs for unsupported single-card choices. Add explicit handler/card rules
and tests for new effects; do not accept an effect merely because its tooltip contains “draw”.
Required choices retain deterministic fallbacks; reserve payment and attack targeting retain
existing heuristics. No shared engine, generated code, or player rules are changed.

Run focused real-runtime checks:

```sh
php -d xdebug.mode=off DevTools/GABotDecisionTest.php
```

Run starter self-play (APCu enabled, or the harness's CLI fallback):

```sh
php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/GABotSelfPlayTest.php --deck=GrandArchiveSim/Tests/BotFixtures/diana-starter.txt --deck2=GrandArchiveSim/Tests/BotFixtures/diana-starter.txt
```
