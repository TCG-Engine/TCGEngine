# IBH — Card Implementation Plan

104 cards total: 2 Leaders, 2 Bases, 80 Units, 20 Events. **50 needs-work, 52 auto-wired** (30 vanilla + 20 keyword-only + 2 blank bases). No new core mechanics — IBH is built entirely on existing engine primitives (heal/deal-damage, draw/discard, exhaust/ready/defeat, return-to-hand, event-driven attack, reveal-top-N, `Action [Exhaust]` abilities, leader deploy + deployed On Attack). All needs-work cards are **autonomous**. Heavy intra-set duplication: the 50 needs-work IDs collapse to ~27 unique effects — duplicates are wired identically (reprint pattern), grouped into the same batch.

### Already Done
IBH_005, IBH_039, IBH_061, IBH_086, IBH_013, IBH_066, IBH_091, IBH_059, IBH_071, IBH_018, IBH_045, IBH_104, IBH_095, IBH_052, IBH_009, IBH_025, IBH_074, IBH_102, IBH_021, IBH_030, IBH_006, IBH_024, IBH_032, IBH_010, IBH_042, IBH_011, IBH_049, IBH_060, IBH_065, IBH_019, IBH_041, IBH_068, IBH_088, IBH_020, IBH_072, IBH_031, IBH_064, IBH_092, IBH_099, IBH_015, IBH_028, IBH_051, IBH_082, IBH_085, IBH_016, IBH_027, IBH_062, IBH_100, IBH_023, IBH_036, IBH_001, IBH_053, IBH_002, IBH_003, IBH_004, IBH_007, IBH_008, IBH_012, IBH_014, IBH_017, IBH_022, IBH_026, IBH_029, IBH_033, IBH_034, IBH_035, IBH_037, IBH_038, IBH_040, IBH_043, IBH_044, IBH_046, IBH_047, IBH_048, IBH_050, IBH_054, IBH_055, IBH_056, IBH_057, IBH_058, IBH_063, IBH_067, IBH_069, IBH_070, IBH_073, IBH_075, IBH_076, IBH_077, IBH_078, IBH_079, IBH_080, IBH_081, IBH_083, IBH_084, IBH_087, IBH_089, IBH_090, IBH_093, IBH_094, IBH_096, IBH_097, IBH_098, IBH_101, IBH_103

## Phase 1 — Damage & heal to a unit/base (autonomous)
- [x] **Batch 1.1 — IBH_005, IBH_039** — done (1636 passing; `IBH_005#0` chained two-enemy damage, UID-excludes the first; reuses DEAL_UNIT_DAMAGE)
  - IBH_005 / IBH_039 I'll Cover For You: deal 1 to an enemy unit and 1 to another enemy unit
- [x] **Batch 1.2 — IBH_061, IBH_086, IBH_013** — done (1639; generic DEAL_UNIT_DAMAGE|3 / HEAL_TARGET|5 over _SWUAllUnits)
  - IBH_061 / IBH_086 We're In Trouble: deal 3 damage to a unit
  - IBH_013 Recovery: heal 5 damage from a unit
- [x] **Batch 1.3 — IBH_066, IBH_091, IBH_059, IBH_071** — done (1643; HEAL_TARGET|2 + DEAL_BASE_DAMAGE|2)
  - IBH_066 / IBH_091 Too Strong for Blasters: heal 2 damage from a unit
  - IBH_059 / IBH_071 Target the Main Generator: deal 2 damage to a base

## Phase 2 — Targeted exhaust / return / defeat events (autonomous)
- [x] **Batch 2.1 — IBH_018, IBH_045** — done (1646; EXHAUST_UNIT over enemy ground only)
  - IBH_018 / IBH_045 Go for the Legs: exhaust an enemy ground unit
- [x] **Batch 2.2 — IBH_104, IBH_095** — done (1650; IBH_104#0 MZMULTICHOOSE-defeat by UID; IBH_095#0 defeat→ready ≤5 power)
  - IBH_104 The Desolation of Hoth: defeat up to 2 enemy units that each cost 3 or less
  - IBH_095 You Have Failed Me: defeat a friendly unit; if you do, ready a friendly unit with 5 or less power
- [x] **Batch 2.3 — IBH_052** — done (1652; IBH_052#0 bounce + same-arena enemy exhaust)
  - IBH_052 Watch This: return a non-leader unit (cost ≤6) to hand, then exhaust each other enemy unit in the same arena

## Phase 3 — Deck reveal / draw / discard events (autonomous)
- [x] **Batch 3.1 — IBH_009, IBH_025** — done (1655; _topDeckSearchBegin + IBH_TOPDECK_DISCARD_FINALIZE = draw unit, discard rest)
  - IBH_009 / IBH_025 I've Found Them: reveal top 3, draw a unit revealed this way, discard the rest
- [x] **Batch 3.2 — IBH_074, IBH_102** — done (1657; draw 2 + DISCARD_FROM_OWN_HAND, excluding the playing event from hand targets)
  - IBH_074 / IBH_102 I Want Proof, Not Leads: draw 2 cards, then discard a card from your hand

## Phase 4 — Event-driven attacks (autonomous)
- [x] **Batch 4.1 — IBH_021, IBH_030** — done (1660; SWUAddAttackPowerBonus+2 + BeginSWUAttack, JTL_231 pattern)
  - IBH_021 / IBH_030 Improvised Detonation: attack with a unit; it gets +2/+0 for this attack

## Phase 5 — Unit On Attack triggers (autonomous)
- [x] **Batch 5.1 — IBH_006, IBH_024, IBH_032** — done (1662; OnAttack deal 1 to ENEMY base directly — a 2-base MZCHOOSE is dropped when attacking a base)
  - IBH_006 / IBH_024 / IBH_032 Rebellion Y-Wing: On Attack: deal 1 damage to a base
- [x] **Batch 5.2 — IBH_010, IBH_042** — done (1664; SWU_DEF_DEBUFF_2 marker in ExecuteSWUAttack + no-op OnAttack stub, like LOF_014)
  - IBH_010 / IBH_042 Han Solo: Raid 2 (auto) + On Attack: the defender gets -2/-0 for this attack
- [x] **Batch 5.3 — IBH_011, IBH_049, IBH_060, IBH_065** — done (1668; _SWUControlsUnitWithAspect gate; R2-D2 MZMAYCHOOSE exhaust ≤4, Piett OnAttack draw)
  - IBH_011 / IBH_049 R2-D2: On Attack: if you control a Command unit, exhaust an enemy ground unit that costs 4 or less
  - IBH_060 / IBH_065 Admiral Piett: On Attack: if you control an Aggression unit, draw a card

## Phase 6 — Unit When Played triggers (autonomous)
- [x] **Batch 6.1 — IBH_019, IBH_041, IBH_068, IBH_088** — done (1673; aspect-gated WhenPlayed: C-3PO draw, Veers base-2 + heal-2)
  - IBH_019 / IBH_041 C-3PO: When Played: if you control a Cunning unit, draw a card
  - IBH_068 / IBH_088 General Veers: When Played: if you control a Vigilance unit, deal 2 to an enemy base and heal 2 from your base
- [x] **Batch 6.2 — IBH_020, IBH_072, IBH_031** — done (1678; Luke may-deal-3; Avenger deal-1-each-other by UID; Falcon ready-if-base-more-damaged)
  - IBH_020 Luke Skywalker: Restore 2 (auto) + When Played: you may deal 3 damage to a ground unit
  - IBH_072 Avenger: When Played: deal 1 damage to each other unit (including friendly units)
  - IBH_031 Millennium Falcon: When Played: if your base has more damage than an enemy base, ready this unit
- [x] **Batch 6.3 — IBH_064, IBH_092, IBH_099** — done (1682; Hoth Lt may-attack-another reuses IBH_021#0; Blizzard One may-defeat ≤3 remaining HP)
  - IBH_064 / IBH_092 Hoth Lieutenant: When Played: you may attack with another unit; it gets +2/+0 for this attack
  - IBH_099 Blizzard One: When Played: you may defeat a non-leader ground unit with 3 or less remaining HP

## Phase 7 — Unit When Defeated triggers (autonomous)
- [x] **Batch 7.1 — IBH_015, IBH_028, IBH_051, IBH_082, IBH_085** — done (1686; WhenDefeated heal-base / opp-discard; test via attacker self-defeat so trigger resolves inline)
  - IBH_015 / IBH_028 / IBH_051 Tauntaun Mount: When Defeated: heal 2 damage from your base
  - IBH_082 / IBH_085 Admiral Ozzel: When Defeated: each opponent discards a card from their hand

## Phase 8 — Action [Exhaust] unit abilities (autonomous)
- [x] **Batch 8.1 — IBH_016, IBH_027, IBH_062, IBH_100** — done (1690; SWUUnitAction: Ion Cannon deal-3-space, Deck Officer heal-2-Villainy)
  - IBH_016 / IBH_027 Ion Cannon: Action [Exhaust]: deal 3 damage to a space unit
  - IBH_062 / IBH_100 Imperial Deck Officer: Action [Exhaust]: heal 2 damage from a Villainy unit
- [x] **Batch 8.2 — IBH_023, IBH_036** — done (1693; SWUUnitAction attack-another-Heroism reuses IBH_021#0)
  - IBH_023 / IBH_036 General Rieekan: Action [Exhaust]: attack with another Heroism unit; it gets +2/+0 for this attack

## Phase 9 — Leaders (autonomous)
- [x] **Batch 9.1 — IBH_001** — done (1696; Leia leader action heal-1 + deployed OnAttack heal 1+1)
  - IBH_001 Leia Organa: Action [1 resource, Exhaust]: heal 1 from a friendly unit; Epic deploy (5+ resources); deployed On Attack: heal 1 from a friendly unit and 1 from another friendly unit
- [x] **Batch 9.2 — IBH_053** — done (1699; Vader leader action deal-1-base + deployed OnAttack deal-2-base)
  - IBH_053 Darth Vader: Action [1 resource, Exhaust]: deal 1 to a base; Epic deploy (6+ resources); deployed On Attack: deal 2 to a base
