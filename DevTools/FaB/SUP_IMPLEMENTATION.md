# Super Slam (SUP)

The pinned catalog contains 276 card identities, including reprints. The builder
accounts for all identities with authored or reused abilities and native rules;
the SUP CardEditor snapshot contains 251 macros. Five supporting SEA Hit macros
apply Catch of the Day to the existing go-fish flows.

Native mechanics live in `FaBSim/Custom/SUPCards.php`. Interactive abilities live
in `build_sup_abilities.py`. Shared engine hooks handle crowd reactions,
Revered/Reviled deck legality, suspense counters and timing, Confidence and
Toughness, hero tapping, base-stat changes, clash replacements, aura control,
Bait ownership and restrictions, and expansion-slot interactions. Suspense
counters have a schema-defined board badge.

Seat-sensitive effects use live participants, with UPF adjacency for targets,
explicit attacker/defender identities, and separate ownership and control.
Private peeks use the viewer's temporary zone. Interactive continuations retain
serializable identities and reacquire objects after player choices.

## Rebuild

From the repository root, using the configured local CardEditor database:

```powershell
python DevTools/FaB/build_sup_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/sup_abilities.json
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/sup_support_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
```

The database name above matches this local installation. Use your configured
database on another installation. Import both snapshots after rebuilding SEA,
since the support snapshot extends its go-fish abilities. Hard-refresh open
boards after regeneration. Do not edit generated PHP or JavaScript.

## Verification

```powershell
php DevTools/FaB/sup_rules_test.php
php DevTools/FaB/sup_games_test.php
php DevTools/FaB/hvy_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/sup_lobby_test.ps1
```

The rules suite includes 1,016 generated continuation paths across two- and
four-seat games, with optional choices accepted and declined. Outcome checks
cover crowd rewards, base stats, live-seat life comparisons, suspense timing,
counter restoration, defense restrictions, clash replacements, ownership,
suppression, cost reduction, hero ready timing, repeated graveyard permissions,
arcane prevention for heroes and allies, and UPF target adjacency.

Complete deterministic fixtures passed for Pleiades/Kayo (392 actions) and
Pleiades/Kayo/Tuffnut/Lyath UPF (704 actions). The fixtures use an aggressive
blocking policy after their opening rounds to bound fatigue games. Local HTTP
lobby creation passed with a SUP deck through both the duel and UPF routes.
The lobby test prints the games it creates. The shared Heavy Hitters suite
passed 460 continuations after the clash code-generation changes.

These are representative automated rules and game-flow checks, not exhaustive
coverage of every card interaction or choice ordering. No manual cross-browser
visual verification was performed for this set.

Rules references: [Super Slam release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/super-slam/)
and [UPF tournament rules](https://rules.fabtcg.com/en/trp/09-special-formats/).
