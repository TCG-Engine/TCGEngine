# TS26 — Card Implementation Plan

88 cards total: 8 Leaders, 4 Bases, 41 Units, 8 Upgrades, 23 Events, 4 Tokens.
78 needs-work, 10 auto-wired (vanilla/keyword-only/token).

No unbuilt core mechanic — every keyword (Hidden, Restore N, Grit, Raid N, Sentinel, Saboteur,
Ambush, Overwhelm, Shielded), Experience/Shield tokens, Capture, Bounty, and Plot (TS26_046 only)
are already implemented. Leader "Epic Action: if you control N resources, deploy this leader"
(001–008) = the engine's existing free-deploy-at-threshold model (threshold = printed deploy
cost). TS26_034 Fives reuses the ASH_230 transplant-abilities code.

### Already Done
TS26_017, TS26_021, TS26_044, TS26_045, TS26_065, TS26_074, TS26_T01, TS26_T02, TS26_T03, TS26_T04, TS26_069, TS26_070, TS26_072, TS26_032, TS26_064, TS26_071

## Phase 1 — Direct unit/base damage (autonomous)
- [x] **Batch 1.1 — TS26_069, TS26_070, TS26_072** — done, 3100/0 (+5 tests). Events via OnPlayEvent + `TS26_069#0`/`070#0`/`072#0` continuations; reused `_SWUUnitHasTrait('Clone')`, `DEAL_UNIT_DAMAGE`, `OnReadyCard`. No new infra.
  - TS26_069 Remove the Chip: deal 2 to a unit; if it's a Clone, ready it
  - TS26_070 Backed by Black Sun: deal 1 to an enemy unit, then may deal damage = # damaged enemy units to a unit
  - TS26_072 Fervor: ready a unit, then deal 3 to a unit
- [x] **Batch 1.2 — TS26_032, TS26_064, TS26_071** — done, 3104/0 (+4 tests). TS26_032 nested play-from-hand −4 + findable-marker deal-4 (SHD_013/SOR_219 pattern, new MARKER registry row); TS26_064 base-damage+draw; TS26_071 `$playCostModifiers` −1/friendly leader unit + deal 3.
  - TS26_032 Reckless Landing: play a unit from hand (-4), then deal 4 to it
  - TS26_064 Urgent Mission: deal 2 to your own base, draw 2
  - TS26_071 Take Action: costs -1 per friendly leader unit; deal 3 to a unit

## Phase 2 — Base healing & Restore-grant passives (autonomous)
- [ ] **Batch 2.1 — TS26_016, TS26_040, TS26_043**
  - TS26_016 King Katuunko: WhenPlayed all units (incl. enemy) gain Restore 1 this phase
  - TS26_040 Obi-Wan Kenobi: passive — other friendly Republic units gain Restore 1
  - TS26_043 Wartime Refugee: OnAttack an opponent heals 1 from their base
- [ ] **Batch 2.2 — TS26_019, TS26_041, TS26_042**
  - TS26_019 Coleman Trebor: Hidden; WhenPlayed deal 1 to each enemy base, heal 1 per damage dealt
  - TS26_041 Twilight: WhenPlayed if 5+ cards in discard, heal 3 from your base
  - TS26_042 Relief Frigate: WhenPlayed choose a base; heal 3 from each OTHER base

## Phase 3 — Stat modifiers (±X this phase / passive) (autonomous)
- [ ] **Batch 3.1 — TS26_048, TS26_081, TS26_033**
  - TS26_048 Vanquish the Legion: give each enemy ground unit -2/-2 this phase
  - TS26_081 Mislead: Shield a unit; give a unit -3/-0 this phase
  - TS26_033 Kouhun Assassination: opponent may discard; if so give a non-Vehicle unit -8/-8 this phase
- [ ] **Batch 3.2 — TS26_036, TS26_050, TS26_075**
  - TS26_036 Tribunal: costs -2 per other card played this phase; WhenPlayed each other unit -2/-2
  - TS26_050 General Grievous: +1/+1 per resource you control; while undamaged gains Sentinel
  - TS26_075 Jango Fett: while enemy attacked your base this phase gains Ambush; OnAttack enemy -3/-0

## Phase 4 — Token / Experience / Shield generators (autonomous)
- [ ] **Batch 4.1 — TS26_023, TS26_049, TS26_055**
  - TS26_023 Assault Lander LAAT: WhenPlayed create 2 Clones; at regroup start deal 4 to this unit
  - TS26_049 Separatist Council: WhenPlayed/OnAttack modal — create Droid OR give 2 Exp to a Droid token
  - TS26_055 Jedi General: Ambush; WhenPlayed per Republic leader you control create Clone + give Exp
- [ ] **Batch 4.2 — TS26_058, TS26_060, TS26_077**
  - TS26_058 Backed by the Pykes: give Exp to friendly; may deal damage = # Exp on friendlies to a unit
  - TS26_060 Take Charge: costs -1 per leader unit; give Exp to each of up to 3 units
  - TS26_077 Deployed Droideka: Ambush; WhenPlayed may pay 2 → give Exp + Shield to self

## Phase 5 — Shield, ready/exhaust, draw effects (autonomous)
- [ ] **Batch 5.1 — TS26_031, TS26_046, TS26_047**
  - TS26_031 Chaotic Diversion: ready an enemy unit (can't attack you this phase); Shield a friendly
  - TS26_046 Secret Marriage: Shield up to 2 non-Vehicle units, draw if enemy shielded; Plot
  - TS26_047 Take Cover: costs -1 per leader unit; heal up to 3 from a unit and Shield it
- [ ] **Batch 5.2 — TS26_068, TS26_076, TS26_082**
  - TS26_068 Arms Deal: you and an opponent each draw 2
  - TS26_076 Wartime Profiteer: WhenDefeated each opponent may ready a resource
  - TS26_082 Evade Arrest: exhaust any number of non-unique units

## Phase 6 — Capture & bounce (autonomous)
- [ ] **Batch 6.1 — TS26_027, TS26_061, TS26_037**
  - TS26_027 Fortune and Glory: WhenPlayed capture a non-leader unit; Bounty (capture a non-leader)
  - TS26_061 Encircle: costs -1 per friendly unit; a friendly unit captures an enemy non-leader in same arena
  - TS26_037 Abandoned the Order: attached loses Jedi, gains Restore 1; WhenPlayed may bounce a non-leader unit

## Phase 7 — Special/conditional units & ability transplant (autonomous)
- [ ] **Batch 7.1 — TS26_013, TS26_020, TS26_024**
  - TS26_013 Darth Sidious: Hidden; other friendly Separatist +1/+0; when a non-token unit defeated create Droid
  - TS26_020 501st Veteran: Grit + Raid 1; while undamaged gains Sentinel
  - TS26_024 Sundari Gauntlet: Sentinel; OnDefense deal 1 to your base
- [ ] **Batch 7.2 — TS26_028, TS26_034**
  - TS26_028 Prime Minister Almec: Saboteur; WhenPlayed give friendly +2/+2, exhaust lower-power enemies in arena
  - TS26_034 Fives: Sentinel; may enter play with another in-play unit's When Played abilities (ASH_230 reuse)

## Phase 8 — Attack-from-effect & combat triggers (autonomous)
- [ ] **Batch 8.1 — TS26_029, TS26_030, TS26_066**
  - TS26_029 Ziton Moj: Ambush; OnAttack for each player deal 1 to a unit that player controls
  - TS26_030 Maul (unit): Sentinel; WhenPlayed may attack with another unit
  - TS26_066 Wartime Pirate: OnAttack an opponent deals 1 to a unit
- [ ] **Batch 8.2 — TS26_025, TS26_083, TS26_084**
  - TS26_025 Fiery Alliance: WhenPlayed may deal 1 to another friendly unit and attack with it
  - TS26_083 Take Aim: costs -1 per leader unit; attack with a unit +2/+0 and gains Saboteur this attack
  - TS26_084 Fearless Attack: attack with a unit +1/+0 per unit the defending player controls

## Phase 9 — Sequential/restricted attacks & base-payoff (autonomous)
- [ ] **Batch 9.1 — TS26_053, TS26_059**
  - TS26_053 Coruscanti Spy: Raid 2; WhenPlayed heal 2 from each of any number of bases
  - TS26_059 Brothers: attack with up to 2 unique units one at a time; prevent all combat damage to each
- [ ] **Batch 9.2 — TS26_062, TS26_067**
  - TS26_062 R2-D2: Raid 2; WhenPlayed may deal 2 to a base; if so that base's controller draws
  - TS26_067 Ruping Rider: Grit; WhenPlayed if your base has 15+ damage, deal 2 to a base

## Phase 10 — Deck & hand search (autonomous)
- [ ] **Batch 10.1 — TS26_018, TS26_039**
  - TS26_018 Jendirian Valley: Restore 1; WhenPlayed search top 8, resource a card
  - TS26_039 Captain Vaughn: Grit; WhenDefeated search top 3 draw one, then put a hand card on top
- [ ] **Batch 10.2 — TS26_056, TS26_057**
  - TS26_056 Galactic Escalation: each player resources the top card of their deck
  - TS26_057 Mechanize: play a non-Vehicle unit from your discard (pay cost) + give it an Exp token

## Phase 11 — Experience payoffs & cost gates (autonomous)
- [ ] **Batch 11.1 — TS26_014, TS26_038**
  - TS26_014 Yoda: costs -2 if you control 7+ resources; WhenPlayed/WhenDefeated create Clone w/ Sentinel this phase
  - TS26_038 Dooku's Solar Sailer: WhenPlayed/OnAttack if a base was healed this phase, give Exp to another Separatist unit
- [ ] **Batch 11.2 — TS26_051, TS26_054**
  - TS26_051 Lom Pyke: WhenPlayed each opp may heal 5; per opp that does give 2 Exp to a unit
  - TS26_054 Wartime Mercenaries: WhenDefeated an opponent may give an Exp token to a unit

## Phase 12 — Upgrades: granted abilities & attach restrictions (autonomous)
- [ ] **Batch 12.1 — TS26_022, TS26_079**
  - TS26_022 The Darksaber: attach non-Vehicle, grant Sentinel; WhenPlayed if 4+ diff keywords among friendlies, ready host
  - TS26_079 Underestimated: attach to a unit that costs 4 or less (non-standard valid-target filter)
- [ ] **Batch 12.2 — TS26_035, TS26_052**
  - TS26_035 Ahsoka's Lightsabers: grant OnAttack/WhenDefeated Shield an enemy → next event -2
  - TS26_052 Sith Traditions: grant OnAttack give Exp to self + WhenDefeated give Exp to a friendly

## Phase 13 — Bases (Epic Actions) (autonomous)
- [ ] **Batch 13.1 — TS26_009, TS26_011**
  - TS26_009 First Battle Memorial: Epic — per friendly leader unit, give Exp to a unit
  - TS26_011 Executioner's Arena: Epic — per friendly leader unit, may deal 2 to a unit
- [ ] **Batch 13.2 — TS26_010, TS26_012**
  - TS26_010 Dooku's Palace: Epic — play a unit from hand, -1 per friendly leader unit
  - TS26_012 Sundari Palace: Epic — per leader unit may resource+ready a card; defeat that many resources at regroup start

## Phase 14 — Leaders: Separatist (autonomous)
- [ ] **Batch 14.1 — TS26_001, TS26_005**
  - TS26_001 Count Dooku: Act 2 players each heal 1 + create Droid; deploy Restore 2 + OnAttack create 2 Droids
  - TS26_005 Savage Opress: passive most-power friendlies gain Overwhelm; deploy Raid 3 + grant Overwhelm to all
- [ ] **Batch 14.2 — TS26_003, TS26_007**
  - TS26_003 Maul: Act/WhenDeployed/OnAttack if more keywords than Exp, give Exp + deal 1
  - TS26_007 Asajj Ventress: Act attack w/ token unit +1/+0; deploy Hidden + while attacked w/ token this phase +2/+0

## Phase 15 — Leaders: Republic & other (autonomous)
- [ ] **Batch 15.1 — TS26_002, TS26_004**
  - TS26_002 Anakin Skywalker: Act if 2+ entered play this phase Shield one; deploy Sentinel + OnAttack Shield an entered unit
  - TS26_004 Padmé Amidala: Act if 2+ entered play attack with one (no bases); deploy OnAttackEnd attack w/ another entered unit
- [ ] **Batch 15.2 — TS26_006, TS26_008**
  - TS26_006 Rex: Act[Exhaust, ready enemy unit] next event -1; deploy OnAttack ready enemy → next event -2
  - TS26_008 Ahsoka Tano: WhenPlayEvent peek top play/discard/leave; deploy Raid 1 + OnAttackEnd top-deck play -1

## Phase 16 — New reactive-trigger seams (pair-programmed)
- [ ] **Batch 16.1 — TS26_063, TS26_073, TS26_078**
  - TS26_063 Rex's DC-17s: grant "when an enemy unit readies during the action phase, ready host (1/round)" — NEW on-ready trigger seam
  - TS26_073 Moralo Eval: Shielded; "when your base is dealt combat damage: may deal 1 to a unit" — NEW base-combat-damage reactive trigger
  - TS26_078 Barriss Offee: Hidden; "when an enemy unit attacks: may give Exp to that unit" — NEW enemy-attack observer trigger

## Phase 17 — Discard-from-opponent's-hand primitive (pair-programmed)
- [ ] **Batch 17.1 — TS26_026, TS26_080**
  - TS26_026 Mother Talzin: Sentinel; WhenDefeated look at opp hand & choose a card to discard, they draw; may play discarded unit ignoring aspect — NEW active-player-chooses-opp-discard primitive (+ existing PlayFromOpponentDiscard)
  - TS26_080 Reveal Intentions: all reveal hands; in player order discard from right-neighbor's hand, then all draw

## Phase 18 — C-3P0 control transfer (pair-programmed)
- [ ] **Batch 18.1 — TS26_015**
  - TS26_015 C-3P0: WhenPlayed an opponent takes control of this unit; Action [Exhaust] deal damage = power to another ground unit, only opponents may use — control-to-opponent + opponent-only activation design fork
