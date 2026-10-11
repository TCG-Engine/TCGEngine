# Compendium of Rathe (PEN)

The printing-based catalog contains 348 distinct card identities. The authoring
builder accounts for the full catalog with 351 saved macros, reused reprints,
and native keyword/continuous-effect handling. It refuses to emit an incomplete
snapshot. `pen_pending.json` is empty after a successful build.

Interactive authoring is in `build_pen_abilities.py`; native rules are in
`FaBSim/Custom/PENCards.php`. The CardEditor import preserves unrelated abilities
and uses revision checks. Generated files must not be edited.

## Rebuild

Run from the repository root:

```powershell
python DevTools/FaB/build_pen_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/pen_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
```

Use the configured CardEditor database on other installations. Hard-refresh
open boards after regenerating.

## Multiplayer rules

- Single hero/ally targets include self when permitted and respect UPF adjacency.
- Effects naming every hero/opponent iterate all live seats. Physical UIDs and
  absolute seat references survive decision-queue continuations.
- Farflight Longbow explicitly permits non-adjacent opposing hero/ally targets.
- Life comparisons, prevention costs, arsenal/graveyard choices, wagers, and
  delayed effects use the affected hero, independent of the turn player.
- Simultaneous Vestige replacements let the affected hero select the replacement.
- Living Legend choices use the imported catalog's `cc_living_legend` metadata;
  update the card catalog when official eligibility changes.

## Verification

```powershell
php DevTools/FaB/pen_test.php
php DevTools/FaB/pen_rules_test.php
php DevTools/FaB/pen_games_test.php
```

The continuation suite exercises 1,884 paths and checks generated macro registration, complete catalog
coverage, and decision completion in two-, three-, and four-seat games with
optional choices accepted and declined. Outcome tests cover targeting, all-seat
effects, prevention, conditional keywords, costs, temporary identity changes,
freeze expiration, Sharpen, wagers, and replacement effects.

The game fixture uses mixed-class PEN cards with the existing generic bot. It is
an engine endurance fixture, not a format-legal deck or a new selectable bot.
The verified seeded run completed a duel in 307 actions and four-seat UPF in 837.
`--trace` prints individual actions for debugging. These tests do not exhaustively
prove every interaction between every card, and do not replace browser playtests.

Shared-regression suites: SUP, HVY, MST, EVO, SEA, plus their WTR/ARC/DYN baseline
checks. Rules references: https://rules.fabtcg.com/en/cr/08-keywords/ and
https://rules.fabtcg.com/en/cr/09-additional-rules/ and
https://rules.fabtcg.com/en/trp/09-special-formats/ (UPF targeting and chain focus).
