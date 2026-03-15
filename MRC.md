# MRC — Unimplemented Cards Analysis

64 of 181 MRC cards are unimplemented. This document organizes them first by mechanical group (cards that share a system and should be implemented together), then by isolated cards bucketed as Easy / Medium / Hard.

---

## Mechanical Groups

---

### 3. Potion Infusion Cycle

Three spells that rest a target Potion and grant it a temporary On-Sacrifice ability until end of turn. They share the same "attach ephemeral ability to a Potion" mechanic; implement the attach mechanism once and each card is a small variation.

- **Growth** uses age counters (different mechanically — no sacrifice trigger).
- **Blaze** and **Seal** both attach an On-Sacrifice ability.

| Card | ID | Effect When Resting a Potion |
|------|----|------------------------------|
| Potion Infusion: Growth | `2898b1w1mv` | Put LV age counters on it (no sacrifice trigger) |
| Potion Infusion: Blaze | `8bki6fxxgm` | Gains "On Sacrifice: deal 4 damage to target attacking ally" until end of turn |
| Potion Infusion: Seal | `om2ry208kk` | Gains "On Sacrifice: Negate target activation unless its controller pays (2)" until end of turn; also has Floating Memory |

---

### 9. Gun Weapons

Two Gun weapons share the Gun mechanic (must be loaded, can't combine with an attack card) with class-bonus interactions on load or activation.

| Card | ID | Gun-Specific Bonus |
|------|----|-------------------|
| Loaded Thoughts | `hh88rx6p3p` | Water Ranger Gun; Class Bonus: whenever this weapon becomes loaded, may put the top card of your deck into your graveyard |
| Framework Sidearm | `p4lgdlx7md` | NORM Ranger Gun; Class Bonus: may pay (3) to activate this card from your material deck |

---

---

## Isolated Cards

Cards with no strong dependency on other unimplemented cards.

---

### Easy

Simple On-Enter triggers, standard keyword application, straightforward damage spells, or single-condition checks.

| Card | ID | Effect Summary |
|------|----|----------------|
| Surging Bolt | `08kkz07nau` | Fire Mage Spell; Imbue 3; deal 3 damage to target champion (4 if imbued) |
| Windmill Engineer | `fz1nr5a3pm` | Wind Ranger Human Ally; Imbue 2; On Enter if imbued: draw a card into your memory |
| Slip Away | `ooffy4dwav` | Umbra Ranger Reaction Skill; Imbue 2; target unit becomes Distant; if imbued: also gains Stealth and Spellshroud |
| Skirting Step | `brq9x9z2k2` | Wind Ranger Reaction Skill; Imbue 2; target unit becomes Distant; if imbued: prevent 1 damage to that unit and draw a card |
| Imperial Scout | `nrow8iopvc` | Water Ranger Human Ally; Ranged 2; whenever CARDNAME becomes Distant, may put top 2 cards of deck into graveyard |
| Andronika, Eternal Herald | `vw2ifz1nr5` | Unique Wind Warrior Automaton Ally; Imbue 3; while imbued: +1 POWER, +1 LIFE, has Vigor; Class Bonus On Enter: put a buff counter on up to two Automaton allies you control |


---

### Hard

Replacement effects, cross-turn stat carry-overs, permanent global state modifications, copy mechanics, arbitrary object cloning, or unusual timing constraints that require significant engine work.

| Card | ID | Effect Summary |
|------|----|----------------|
| Diablerie | `0plqbtjuxz` | Wind Cleric Reaction Spell; the next time a divine relic Regalia would enter the field this turn, it enters under your control instead; Vanitas Bonus: Floating Memory |
| Atmos Shield | `80yu75k0hl` | TOKEN NEOS Guardian Automaton Ally; REST: prevent the next 2 non-combat damage dealt to a target neos element unit this turn; passive: whenever another neos element unit you control is targeted for an attack, may redirect that attack to CARDNAME instead |
| Blessed Clergy | `a3pmmloejo` | Wind Cleric Human Ally; Imbue 2; Class Bonus On Enter if imbued: target player cannot play more than two cards during their next turn (requires per-player turn-scoped card-play counter) |
| Echoic Guard | `gn1b2sbrq9` | NEOS Guardian Reaction Skill; Class Bonus costs 1 less; prevent the next 2 damage to target ally; then may pay (X) where X = that ally's reserve cost to summon a token copy of that ally |
| Naia, Diviner of Fortunes | `jdmthh88rx` | Unique Water Mage Human Ally; Class Bonus On Enter: reveal top 3, choose 1 to banish (rest go to GY); if banished card is a Spell, you may activate it as long as you control CARDNAME (persistent banished-card activation window) |
| Gearstride Academy | `lxnq80yu75` | Unique Wind Guardian Domain Castle; wind ally cards you activate gain Imbue 2; wind allies have "On Enter: if this ally is imbued, gets +1 POWER until end of turn"; Upkeep: pay (1) at start of recollection phase or sacrifice this |
| Eternal Magistrate | `taug52u81v` | Wind Cleric/Tamer Automaton Ally; Imbue 2; while imbued: cards can't leave opponents' material decks unless it is their materialize phase (requires global gate on material-deck card removal) |
| Refracted Twilight | `me0xxw0plq` | Astra Cleric Potion Item; Brew — Two Silvershine, Three Herbs; Banish CARDNAME: the next time you activate an ability of target Potion this turn, copy that ability twice (you may choose new targets for those copies) |
| Vainglory Retribution | `qtzsekkjn3` | Wind Cleric Reaction Spell; Vanitas Bonus L2+: costs 2 less; prevent the next ≤4 combat damage to your champion this turn; your champion's first weaponless attack during your next turn gets +X POWER where X = damage prevented (cross-turn power carry-over dependent on prevention amount) |
| Curse Amplification | `x9z2k2a5ig` | Umbra Ranger Skill; Diana Bonus: costs 3 less; if champion has ≥20 damage, Recover 4; for the rest of the game, Curse cards in lineages have "Inherited Effect: at the beginning of your recollection phase, deal 1 unpreventable damage to this object" (permanent global lineage modification) |
| Orchestrated Seizure | `pwscn0esog` | Wind Cleric Skill; activate only during an opponent's end phase; during your next materialize phase, you may banish Floating Memory cards from opponents' graveyards to pay for memory costs (non-standard timing window + cross-phase payment flag) |
