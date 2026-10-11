# High Seas (SEA)

The checked-in catalog and CardEditor snapshot cover all 265 SEA identities,
including reprints. The builder preserves existing reprint implementations and
fails if a new identity has no authored or explicitly supported native behavior.
There are 286 saved macros.

`SEACards.php` contains choice and object helpers; `SEARuntime.php` contains
activation, combat, movement, turn, and hero hooks. Saved interactive effects
remain in `build_sea_abilities.py`, rather than generated PHP.

Implemented foundations include hero/ally taps, ally attack costs, companions,
Crank and Cogs, Watery Grave and face-down graveyards, Gravy Bones's graveyard
permission, Marlynn's arsenal triggers, Puffin's second crank, Scurv and Goldkiss
Rum, Gold transfers, High Tide, Go Fish, and Treasure Island counters. Treasure
Island applies when the macro is present in the arena; ordinary games do not
automatically receive the limited-format macro.

SEA macros retain the effect controller in a serializable local across choices
made by another seat. This is essential for Go Fish and damage prevention.
Face-down grave cards are omitted from ordinary candidates and concealed in
the server response to other seats. The owner retains a muted card face.
Graveyard cards expose normal card actions when Gravy Bones makes them playable.

The generator wraps creation helpers in hero macros so Preach Modesty remains
effective after an await; unrelated card effects still create cards normally.
The graveyard `FaceDown` field is appended to preserve older save layouts.

## Rebuild

From the repository root, with the local CardEditor database configured:

```powershell
python DevTools/FaB/build_sea_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/sea_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
```

Hard-refresh open boards after regeneration. Never edit generated card macros.

## Verification

```powershell
php DevTools/FaB/sea_rules_test.php
php DevTools/FaB/sea_games_test.php
powershell -NoProfile -File DevTools/FaB/sea_lobby_test.ps1
php DevTools/FaB/sea_ui_fixture.php
python DevTools/FaB/sea_privacy_test.py
```

The continuation suite exercises 1,144 macro paths across duels and UPF, taking
and declining optional choices. Outcome tests cover costs, tap state, graveyard
entry/replacement, hidden cards, Go Fish ownership, Cogs, hero triggers, damage,
temporary control, additional attack targets, and activation limits.

The deterministic full-game fixtures completed in 273 actions (Gravy/Marlynn)
and 677 actions (Gravy/Marlynn/Puffin/Scurv). The lobby smoke test exercises a
40-card Gravy Bones deck through both public API creation routes. Existing
set rule suites and the 33 shared JavaScript tests also passed.

Browser fixtures use reserved games `98026502` and `98026504`; the script refuses
to overwrite an existing directory unless it contains `.sea-test-fixture`.
They can be removed after testing. The lobby smoke test prints the separate
numbered test games it creates.

Rules reference: [Comprehensive Rules, keywords](https://rules.fabtcg.com/en/cr/08-keywords/).
Card text and printings are pinned in `sea_catalog.json` from the local card data.
