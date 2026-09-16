# Usurp the Shadow Throne (IAR)

The snapshot covers 261 distinct IAR card identities, including reprints. New
abilities use the CardEditor repository and generated continuations. Shared
rules live in `FaBSim/Custom/IARCards.php` and `IARRuntime.php`.

The import schema pins fab-cube commit
`0ef333c01b7c933a03f3b2762cdac89a73e8e204`. Its preview transcription of blue
Darkest Hour is corrected to +2 in the importer and authored effect, following
the [official release notes](https://fabtcg.com/rules-and-policy-center/release-notes/usurp-the-shadow-throne/).
The catalog preserves the source transcription for auditing.

## Rebuild

Run from the repository root, with PHP and Python on PATH. Configure CardEditor
database access for your installation (`DEVENV=true` and
`MYSQL_DATABASE_NAME=swuonline` for this local XAMPP installation).

```powershell
php zzCardCodeGenerator.php rootName=FaBSim withPreview=1 downloadImages=0
python DevTools/FaB/build_iar_abilities.py
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/iar_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/iar_assets.php
```

The asset command needs Imagick and network access. It uses the normal image
converter for card, concat, and crop images. Hard-refresh the client after
generation. Do not manually edit generated PHP or JavaScript.

## Multiplayer rules

- State and permission records use absolute seat IDs and turn numbers.
- Usurp is an untargeted cost and can destroy any public Runechant, including a
  nonadjacent opponent's. Sin Runechant rewards stay with their controller;
  attack modifiers follow the usurping attack UID.
- Targeted effects use live UPF adjacency; all-hero effects and untargeted costs
  use their printed scope. Eliminated seats are excluded.
- Gate and Malice permissions track the card, seat, zone, and turn. A foreign
  banished card does not count as played from your own banished zone.
- Blasmophet permissions are declared and consumed per permanent. Unique offers
  a choice of which copy to clear.
- Decay precedes ally healing. Corpses created after the end phase begins do not
  retroactively add blood-debt triggers. Incarnate ceasing to exist does not
  produce death triggers.
- Bound Marks follow the ally source; Shadow Resist checks the damaging hero's
  talent; Wind Slicer suppresses the affected hero's next action phase.

## Validation

```powershell
php DevTools/FaB/iar_test.php
php DevTools/FaB/iar_rules_test.php
php DevTools/FaB/iar_games_test.php
```

The continuation suite checks all saved macros and exercises optional branches
with two, three, and four seats. The outcome suite asserts rules-sensitive
results in duels and four-seat UPF, and includes the existing WTR, ARC, and DYN
regressions. The seeded games use mixed IAR fixtures and the existing Professor
bot; they are engine regression fixtures, not format-legal deck recommendations.
Use `--trace` to inspect individual match steps. Full bot matches can take
several minutes.

These checks are not exhaustive proofs of every cross-set interaction. Existing
engine trigger ordering and response-window behavior still apply.
