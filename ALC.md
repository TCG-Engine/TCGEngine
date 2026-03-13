# ALC — Unimplemented Cards Analysis

### 1. Diana Champion Lineage
*These five cards form a tightly coupled system built around the Umbra Curse-in-lineage archetype. Diana Duskstalker's `Generate` ability produces Creeping Torment and requires it to exist. Diana Cursebreaker needs 4+ Curses already in the lineage, making the overall build-up critical. Code Creeping Torment first.*

| Card | ID | Note |
|------|----|------|
| Diana, Keen Huntress | `e3z4pyx8bd` | L1; Lineage Release — materialize a Gun from material deck |
| Diana, Deadly Duelist | `7ozuj68m69` | L2; On Enter: materialize a Bullet; Inherited: Ranged 2 |
| Diana, Duskstalker | `iq4d5vettc` | L3; On Enter: becomes distant; On Champion Hit: **Generate** Creeping Torment to lineage |
| Diana, Cursebreaker | `o0qtb31x97` | L3 alt; banish 4+ Curses → materialize 2 Bullets + gains "On Attack: wake up Diana" |
| Creeping Torment | `zrplywc08c` | Phantasia Curse; On Enter: goes to lineage; Inherited: whenever controller draws their 2nd card each turn, deal 2 unpreventable to champion |

---

### 5. Curse Lineage Package
*These Umbra/Ranger cards all interact with the "Curses in champion's lineage" mechanic — either adding themselves to the lineage as Curse cards (each carrying a punishing Inherited Effect of -2 Life), counting existing Curses, or removing them. Note that Load Soul and Gloamspire Lance (in the Gun group), Anathema's End (in the Bullet group), and Creeping Torment (in the Diana lineage) also belong to this ecosystem; implement those first.*

| Card | ID | Note |
|------|----|------|
| Shadecursed Hunter | `oqk2c7wklz` | Ally; Ranged 5, Stealth; On Death: add itself to bottom of champion's lineage; Inherited: -2 Life |
| Violet Haze | `vdxi74wa4x` | Action Curse Spell; all your units become distant; puts itself on target champion's lineage; Inherited: -2 Life |
| Demon's Aim | `6g7xgwve1d` | Action Curse Spell; puts itself on bottom of lineage; champion's attacks this turn gain True Sight, ignore Taunt, and can't be redirected by Intercept; Inherited: -2 Life |
| Umbra Sight | `f15joh300z` | Action Curse Spell; draw a card; option to also draw into memory and put itself on lineage, dealing 2 unpreventable per existing Curse in lineage |
| Exorcise Curses | `u1xhs5jwsl` | Action; choose up to 2 Curse cards from a champion's lineage and discard them; Floating Memory |

---

### 12. Vanitas Convergent Ruin + Dominating Strike
*Dominating Strike has an explicit Vanitas Bonus alternative cost.*

| Card | ID | Note |
|------|----|------|
| Vanitas, Convergent Ruin | `8m69iq4d5v` | L2 Champion; whenever you activate a Spell → next weaponless attack this turn gets +1 power; On Champion Hit: if 7+ damage dealt → opponent's materializes cost 1 more until your next turn |
| Dominating Strike | `svd53zc9p4` | Attack 4 Fist; weapons can't be used for this attack; Vanitas Bonus: may reveal 3 wind cards from memory as alt cost |

---

### 14. Negate Package
*Flash Freeze and Tether in Flames are both Reaction Negate cards with different payment conditions. The Constellatory Spire triggers whenever you negate, rewarding the archetype with chip damage. All three should be wired up together once the Negate activation logic and its trigger window are confirmed.*

| Card | ID | Note |
|------|----|------|
| Flash Freeze | `w3rrii17fz` | Reaction; Negate target activation unless its controller pays (LV); that card is then banished; Class Bonus: costs 2 less |
| Tether in Flames | `215upufyoz` | Reaction; Negate target activation unless its controller has this deal 1+LV unpreventable damage to their champion; Class Bonus: costs 2 less |
| The Constellatory Spire | `yd609g44vm` | Domain Spire; On Enter: draw; whenever you negate an activation → may rest this to deal 2 to target unit; Class Bonus: costs 2 less |

---

---

## Isolated Cards

Cards with no strong mechanical dependency on other unimplemented cards. Bucketed by implementation effort.

---

### Medium

Multiple triggers, conditional passives, multi-step On Enter choices, delayed effects, or moderate zone-state checks.

| Card | ID | Effect Summary |
|------|----|----------------|
| Rose, Eternal Paragon | `2bbmoqk2c7` | Unique Ally Automaton; Intercept, True Sight; Class Bonus: Fast Activation; [Level 2+] On Enter: may redirect an ongoing attack to Rose, she gets +1 Life if so |
| Provoke Obstinance | `16r0zadf9q` | Reaction Spell; up to 5 targets gain Spellshroud; prevent 2 damage to each that are units; Class Bonus: if exactly 1 target → draw into memory |
| Cyclonic Strike | `3ir1o0qtb3` | Attack 4 Sword; Class Bonus (0) in-intent ability: Suppress target opponent ally, CARDNAME gets -2 power; activate only once; only while in intent |
| Fireblooded Oath | `bmoqk2c7wk` | Action Spell; additional cost: banish 3 fire cards from GY; level up your champion; at the beginning of the next end phase → delevel; Class Bonus: costs 2 less |
| Tonoris, Creation's Will | `n2jnltv5kl` | L3 Champion; if you would summon tokens, may summon that many Aurousteel Greatsword tokens instead; token weapons gain "Sacrifice this: target weapon gets +X power where X = this object's power" |

---

### Hard

Complex replacement effects, permanent global state modifications, copy mechanics, during-payment triggers, or unusual timing windows requiring significant engine work.

| Card | ID | Effect Summary |
|------|----|----------------|
| Clockwork Amalgam | `3zc9p4lpnv` | Phantasia; enters as a copy of any ally or weapon on field, but adds a bounce ability: "at beginning of your recollection phase, may return this to hand" |
| Astarte, Celestial Dawn | `f0ht2tsn0y` | Unique Ally; Class Bonus: Fast Activation; replacement effect: if any object would enter the field under an opponent's control from anywhere except the effects stack, banish it face down instead |
| Dusklight Communion | `5upufyoz23` | Unique Phantasia; additional cost: banish an astra or umbra card from your material deck; if astra → destroy target phantasia; if umbra → this card gains "Champions get -1 level" as a global passive effect |
| Temporal Spectrometer | `h23qu7d6so` | Regalia Artifact (Divine Relic); REST: add a time counter; while paying a memory cost, may sacrifice this to pay for X of that cost (X = time counters); requires during-payment interaction |

---
