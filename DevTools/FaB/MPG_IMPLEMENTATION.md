# Mastery Pack: Guardian (MPG)

The catalog covers 130 card identities: 73 new implementations and 57 reused
identities. The reproducible CardEditor snapshot contains 91 macros. New native
rules live in `FaBSim/Custom/MPGCards.php`; interactive choices are authored in
`build_mpg_abilities.py`. Generated files must not be edited directly.

Support includes clashes (including Victor retries), Heave 2 and 3, Seismic
Surge creation and replacement, Valda and Jarl, equipment and aura control,
face-up arsenal permissions, next-turn restrictions, variable costs, and
multi-link defense effects. All table-wide operations use live seats. Interactive
macros preserve the original controller across choices made by other players.
Restrictions advance and expire on the named player's turns, not on a fixed
number of turns; arsenal theft also checks that both players remain in the game.
Jarl's Frostbites retain their occupied equipment slot and display that slot on
the board. Geysers display their energy counters.

The defense-trigger integration repairs the shared trigger resolver's return
from DEFEND to DEFEND_PRIORITY. The waiting indicator now respects a viewer's
pending choice even when another hero retains priority. FaBSim counter rules
are written separately so the schema parser preserves every badge's parameters.

## Rebuild

From the repository root, with the local CardEditor database configured:

```powershell
python DevTools/FaB/build_mpg_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/mpg_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
```

Hard-refresh open boards after regeneration.

## Verification

```powershell
php DevTools/FaB/mpg_rules_test.php
php DevTools/FaB/mpg_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/mpg_lobby_test.ps1
php DevTools/FaB/mpg_ui_fixture.php
node --test DevTools/tests/fab-mpg-decisions.test.mjs
```

The continuation suite exercises 352 paths across two- and four-seat games,
accepting and declining optional choices. Outcome checks cover clash wins,
losses and ties; cross-seat choices; token batching and expiry; physical ownership;
equipment capacity; dynamic defense; X payment; Heave payment; suppression;
arsenal permission; and eliminated opponents.

Deterministic complete games finished in 401 actions (Valda/Bravo duel) and
612 actions (Valda/Bravo/Victor/Jarl UPF). The full-game fixtures use an aggressive
blocking policy after the opening rounds to keep fatigue matches bounded.
Both lobby creation routes passed with a 40-card Guardian deck.
Browser verification exercised a seat-four block, clash win, and selection of
seat one's aura, and confirmed Geyser and Frostbite badges. No browser errors
were recorded. All 34 shared JavaScript tests passed.

Browser fixtures reserve games `98013002` and `98013004` and refuse to overwrite
an existing directory without `.mpg-test-fixture`. They may be removed after
verification. The lobby test prints the separately numbered games it creates.

Card text is pinned in `mpg_catalog.json` from the local generated dictionary.
Rules references: [keyword rules](https://rules.fabtcg.com/en/cr/08-keywords/)
and [zones](https://rules.fabtcg.com/en/cr/03-zones/).
