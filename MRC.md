# MRC — Unimplemented Cards Analysis

64 of 181 MRC cards are unimplemented. This document organizes them first by mechanical group (cards that share a system and should be implemented together), then by isolated cards bucketed as Easy / Medium / Hard.

---

## Mechanical Groups

---

### 1. Tristan Lineage & Preparation Counter System

The Tristan champion line is built entirely around **preparation counters** accumulated on the champion. The champions themselves, their weapon/skill support, and the generated Ominous Shadow token all depend on this counter resource and should be wired up as a cohesive system.

**New engine needs:**
- `preparation` counter on the champion object (persists across turns).
- **Tristan Bonus** champion-check (mirror of Vanitas Bonus).
- Champion level-up draw hook (Tristan Lineage: draw 2 on level-up).
- Tristan, Shadowreaver: banish top 4 of opponent's deck face-down and allow them to be played ignoring element — this is the hardest piece; requires tagged banish + a cross-phase "you may activate this" window.
- Shadow's Claw: Phantasia allies using a weapon to attack is a novel interaction.

| Card | ID | Note |
|------|----|------|
| Tristan, Underhanded | `bjlwabipl6` | L1 Champion; On Enter: put a preparation counter on Tristan OR gain Agility 3 for this turn |
| Tristan, Hired Blade | `gt7lh9v221` | L2 Champion; On Enter: draw if ≥2 prep counters; draw extra if ≥4 prep counters |
| Tristan, Shadowreaver | `4upufooz13` | L3 Champion; Tristan Lineage: level-up draws 2; On Enter: banish top 4 of opponent's deck face-down, you may play them ignoring element reqs |
| Sadi, Blood Harvester | `ugly4wiffe` | Unique NORM Assassin Ally; (2): return to hand, put prep counter on champion; Class Bonus On Kill: Agility 3 |
| Mercenary's Blade | `k0xhi5jnsl` | NORM Dagger; Class Bonus: remove a prep counter from champion to activate from material deck |
| Shadow's Claw | `vm4kj3q2sv` | Umbra Dagger; as long as ≥4 prep counters, activate from material deck; Tristan Bonus: Phantasia allies can attack using this weapon; put durability counter on when they do |
| Penumbral Waltz | `nt1lyk1dvd` | Umbra Reaction Skill; additional cost: remove X prep counters from champion; prevent next X+3 damage to champion; Tristan Bonus X≥3: summon 2 Ominous Shadow tokens |
| Gloamspire Mantle | `fooz13xfpk` | Umbra Assassin Accessory; Tristan Bonus On Enter: may pay (3) to summon an Ominous Shadow token; umbra element Phantasia allies you control have Ambush |
| Scorching Trap | `wjbqjdmthh` | Fire Reaction Skill; Class Bonus: if not your turn, remove a prep counter to activate from memory for free; deal 2 damage to target attacking unit |
| Collapsing Trap | `v2214upufo` | Water Reaction Skill; Class Bonus: if not your turn, remove a prep counter to activate from memory for free; the next time allies would enter the field this turn, they enter rested |
| Ominous Shadow | `gveirpdm44` | TOKEN Umbra Phantasia Ally; Unblockable; prevent 3 damage to it; can only attack units your champion has dealt combat damage to this turn |

---

### 2. Imbue Multi-Modal Decrees

Three spells share an identical Imbue-3 structure: choose 1 option; if imbued, choose 2. Once the template is proven for one, the other two are trivial.

| Card | ID | Options |
|------|----|---------|
| Verdigris Decree | `7cx66hjlgx` | Suppress ally / +2 POWER to ally / Destroy target Phantasia |
| Cerulean Decree | `ipl6gt7lh9` | Negate activation (unless pay 2) / target unit attacks get -3 POWER / draw into memory |
| Vermilion Decree | `tjej4mcnqs` | Deal 3 to target champion / deal 2 to target ally / each player draws a card |

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

### 4. Unit Link Shields

Three Shield Regalia all use Unit Link to protect the linked unit. The core Unit Link mechanism already exists; each card simply adds a passive protection rule tied to the link.

| Card | ID | Linked-Unit Bonus |
|------|----|--------------------|
| Prototype Shield | `zadf9q1vk8` | Class Bonus: prevent 3 damage while linked unit is attacking |
| Vaporjet Shield | `y208kkz07n` | Class Bonus: prevent 1 damage to linked unit |
| Winbless Kiteshield | `uoy5ttkat9` | Class Bonus costs 1 less; linked unit has Vigor |

---

### 5. Powercell Token Synergy

Several cards generate, count, or consume the Powercell token (already defined as a card in the set). The cluster below should be implemented together once it is confirmed the Powercell token (card `qzzadf9q1v`) resolves correctly from summon sources.

| Card | ID | Role |
|------|----|------|
| Cell Converter | `eqhj1trn0y` | At beginning of end phase, summon a Powercell token rested; Class Bonus: Floating Memory |
| Cell Forging | `pufooz13xf` | Choose: 2 durability counters on target weapon OR summon a Powercell token |
| Charged Assailant | `ffy4dwavco` | Wind Automaton; Class Bonus On Enter: if you control a Powercell, gain Agility 3 |
| Alchemical Scripture | `h9v2214upu` | Neos Regalia Book; at beginning of end phase, if you control 4+ tokens, draw a card into memory |
| Overlord Mk III | `sl7ddcgw05` | Unique NORM Guardian Automaton; additional cost: sacrifice 4 Powercells; Intercept / Spellshroud / Steadfast / True Sight; at beginning of end phase: may banish an Automaton from GY → buff counter + draw |

---

### 6. Starcall / Astra Mechanics

Three cards revolve around the Starcalling mechanic and the Astra element. Meteor Strike uses existing Starcall engine behavior. Lunar Conduit is a persistent artifact with charge counters. Stargazer's Portent (the copy effect) is the hardest of the three.

| Card | ID | Note |
|------|----|------|
| Meteor Strike | `dwavcoxpnj` | Starcalling (3); while being starcalled: deal 3 damage to all units except your champion; otherwise: destroy target non-champion object |
| Stargazer's Portent | `btjuxztaug` | Astra Cleric Skill; Class Bonus costs 1 less; the next time you starcall a card this turn, copy that activation (new targets optional) — **Hard** |
| Lunar Conduit | `0yetaebjlw` | Astra Regalia Staff; Class Bonus costs 1 less; whenever you activate an astra element card, put a charge counter on it; REST (3): as a Spell, deal damage to target unit = charge counters, then remove one charge counter |

---

### 7. Foster Allies

Two allies share the Foster keyword with different passive bonuses that activate while fostered. If the Foster keyword is already in the engine, both are minor stat-modifier additions.

| Card | ID | While Fostered |
|------|----|----------------|
| Krustallan Patrol | `8sugly4wif` | On Foster: put a buff counter; gains Steadfast (retaliates while rested, doesn't rest to do so) |
| Embershield Keeper | `xhi5jnsl7d` | Gets +2 LIFE |

---

### 8. Tamer / Beast Synergy

Three Tamer-class cards that buff, generate, or recover Animal and Beast allies.

| Card | ID | Note |
|------|----|------|
| Spirited Falconer | `a5igwbsmks` | Wind Tamer Human Ally; Class Bonus: Fast Activation; Class Bonus On Enter: put a buff counter on up to two target Animal and/or Beast allies |
| Seaside Ringleader | `eirpdm44nt` | Water Tamer Human Ally; Class+Element Bonus GY activation: (2), banish this → draw into memory; while active this turn, Animal/Beast allies you activate enter with an extra buff counter |
| Automaton Beastkeeper | `i5jnsl7ddc` | NORM Tamer Automaton Ally; Class Bonus On Enter: may return a Beast ally card from your graveyard to your memory |

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

### Medium

Multiple conditions, zone-state lookups, GY-activation gates, recollection-phase triggers, or mechanics that require moderate engine hooks.

| Card | ID | Effect Summary |
|------|----|----------------|
| Hailstorm Guard | `05qzzadf9q` | Water Guardian Reaction Spell; Class Bonus costs 2 less; prevent the next damage dealt to your champion this turn; then mill from top of deck equal to the amount prevented |
| Fractal of Rain | `3zb9p4lgdl` | Water Cleric Phantasia Fractal; Imbue 2; Reservable; at beginning of your recollection phase, if imbued: target player mills 1 from deck |
| Tideholder Claymore | `5iqigcom2r` | Water Guardian Sword Weapon; Class Bonus costs 1 less; additional cost to attack: pay (10) reduced by (1) for each water element card in your graveyard |
| Gloamspire Sniper | `6hjlgx72rf` | Umbra Ranger Human Ally; Ranged 4, True Sight; Class Bonus On Kill: Generate a Creeping Torment card and put it on the bottom of target champion's lineage |
| Frost Shard | `jnsl7ddcgw` | Water Mage Spell; Class Bonus: if your champion has leveled up this turn, may activate from graveyard (banish on resolve); deal 2 damage to target unit (3 if that unit is rested) |
| Sinister Mindreaver | `jozihslnhz` | Umbra Assassin Human Ally; Class Bonus: Fast Activation, Ambush; On Champion Hit: look at that opponent's memory, may discard up to 2 cards from it, they draw that many into their memory |
| Suffocating Miasma | `coxpnjvt9y` | Umbra Assassin Phantasia; Imbue 2; On Enter if imbued: put 3 debuff counters on target ally; at the beginning of each opponent's recollection phase: they put a debuff counter on an ally they control or take 2 unpreventable damage to their champion |
| Mechanized Smasher | `qsm3n9yvn1` | Wind Guardian Fist Weapon; Class Bonus costs 1 less; can't be used with attack cards; additional cost to attack: reveal four wind element cards from your memory |
| Surge Protector | `qigcom2ry2` | Fire Guardian Skill; until end of turn, target Shield item you control gains "If damage would be dealt to your champion, prevent that damage and sacrifice this object instead" |
| Cultivate | `cy3gme0xxw` | Wind Cleric Skill; Imbue 1; for every three Herb items you control, Gather; if imbued: Gather one additional time |
| Clockwork Musicbox | `q2svdv3zb9` | NORM Tamer Artifact Regalia; Hindered (enters rested); Class Bonus: whenever you activate a Harmony or Melody card from hand, may banish it as it resolves; REST: activate a card banished by CARDNAME (you still pay its costs) |
| Krustallan Ruins | `fei7chsbal` | Water Cleric Domain Ruins; whenever any ally enters the field under any player's control, rest that ally unless that player pays (1) |

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
