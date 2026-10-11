# Omens of the Third Age (OMN)

The printing-based catalog contains 251 distinct identities. The reproducible
builder emits 298 macros, reuses existing reprints, and accounts for cards handled
by native keyword and event hooks. It fails if the set is incomplete.

Authoring lives in `build_omn_abilities.py`; runtime helpers are in
`FaBSim/Custom/OMNCards.php`. Import through the configured CardEditor repository
and regenerate; never edit generated macros or generated UI files.

```powershell
python DevTools/FaB/build_omn_abilities.py
# Use this installation's configured card-code database.
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/omn_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/omn_test.php
php DevTools/FaB/omn_rules_test.php
php DevTools/FaB/omn_games_test.php
```

The importer preserves unrelated abilities and rejects conflicting authored
bodies. Rebuilds retain migration hashes. Hard-refresh existing games after
regenerating to load the new UI, including holo counter badges.

## Multiplayer and timing

- Single-target choices use absolute seat references and UPF adjacency; effects
  affecting each hero iterate the live seats. Mandatory discards belong to the
  affected hero, independently of the turn player and combat focus.
- Fragment triggers for every eligible defending card, including defense
  reactions. Fragment rewards, Lightning Flow destruction, Starfall, and hero
  readiness remain specific to the source/controller and turn.
- Starfall observes instants entering the graveyard from any zone. Temporary
  graveyard permissions expire with the turn. Holo entry resets the returning
  permanent and uses pitch-specific Ward values.
- Arcane packets use generated barrier, Spellvoid, Quell, and Ward choices.
  Spellbane Sigil offers a numeric X before resource payment, including in
  existing sets' generated barrier flows.
- Hero/equipment tap and token costs use the activation payment path. Conditional
  go again on resolving non-attacks grants its action point after its choice.
- In limited fixtures, put `omens_of_arcana` in a player's inventory before
  `FaBOMNSetup()`. Normal game creation calls this after initializing all seats;
  the macro creates one Lightning Flow per hero and grants their Spellvoid.
  Constructed games do not enable the limited macro automatically.

## Verification

The continuation suite exercises 1,632 non-modifier paths across two, three, and
four seats with optional choices accepted and declined. Outcome tests cover
targeting, repeated Fragment, Starfall expiration, holo Ward scaling, Quickstrike,
prevention rewards, temporary control, variable barrier payment, awaited go again,
and Zyggy's costs and return effect.

The seeded game fixture uses mixed-class OMN cards and the existing generic bot;
it is an engine endurance test, not a format-legal deck or a selectable OMN bot.
`--trace` prints actions for debugging. These checks are representative regressions,
not an exhaustive proof of all card combinations. Browser playtesting in Chromium,
Firefox, and Safari has not been performed for this set.

Rules references:
[keywords](https://rules.fabtcg.com/en/cr/08-keywords/),
[combat](https://rules.fabtcg.com/en/cr/07-combat/), and
[UPF](https://rules.fabtcg.com/en/trp/09-special-formats/).
