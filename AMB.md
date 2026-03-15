# AMB — Unimplemented Cards Analysis

78 of 189 AMB cards are unimplemented. This document organizes them first by mechanical group (cards that share a system and should be implemented together), then by isolated cards bucketed as Easy / Medium / Hard.

---

## Mechanical Groups

---

### 2. Jin Lineage & Polearm Support

The Jin champion line centers on Polearm weapons and an aggressive cross-turn attack buff strategy. Jin, Undying Resolve's **immortality** mechanic — the champion cannot die except during the end phase — is the hardest piece, requiring a phase-gated death-prevention hook.

**New engine needs:**
- **Jin Bonus** champion-check (may already be present from the existing Jin champion in a prior set).
- **Phase-gated immortality**: hook death/damage-lethal resolution to check both the keyword and the current phase; only allow death during end phase.
- **Preserve** keyword for Fang of Dragon's Breath: a Phantasia weapon-link that must die when its linked weapon leaves the field, with a REST activated ability conditionally added by Jin Bonus.

| Card | ID | Note |
|------|----|------|
| Jin, Fate Defiant | `zd8l14052j` | L1 Champion; Inherited Effect: whenever Jin attacks with a Polearm weapon and/or Polearm attack, target Horse or Human ally you control gets +1 POWER until EOT |
| Jin, Undying Resolve | `c4yrrtv7o1` | L3 Champion; Jin Lineage; as long as it's not your end phase, Jin has Immortality (can't die) |
| Beseeching Flourish | `d60jobz3ct` | Wind Warrior Polearm Attack 3; Jin Bonus On Hit: Materialize a Polearm weapon card from your material deck (you still pay costs) |
| Fang of Dragon's Breath | `iebo5fu381` | Fire Warrior Phantasia; Polearm Weapon Link (enters linked to a Polearm; dies if link breaks); linked weapon gets +2 POWER; Jin Bonus: linked weapon gains "REST, Remove a durability counter: deal 2 damage to target unit" |
| Fraternal Garrison | `ln926ymxdc` | Wind Warrior Ally; Jin Bonus: whenever another ally enters the field under your control, CARDNAME gets +1 POWER until EOT |

---

### 3. Equestrian Keyword Cluster

Ten Human allies share the **Equestrian** keyword, granting cost reductions, conditional passives, or On-Enter effects that require controlling at least one Horse ally. The Equestrian keyword check is the shared primitive; once confirmed working, each card is a small variation.

**New engine needs:**
- `ZoneSearch("myField", cardSubtypes: ["HORSE"])` condition — if Horse subtype query already works, no new engine primitive is needed.
- Persistent stat passives (War Marshal, Nanyue Portsman, Determined Spearman) must re-evaluate dynamically as Horses enter or leave the field mid-turn.

| Card | ID | Equestrian Bonus |
|------|----|-----------------|
| Stolid Vanguard | `yrm3xibmoz` | On Enter: if you control a Horse ally → CARDNAME gets +2 POWER until EOT |
| Determined Spearman | `c8z5ntioqs` | Costs 1 less while you control a Horse; separate passive: Level 1+ → +1 LIFE |
| Skilled Plainsman | `55u41ilks4` | Class Bonus On Enter: if you control a Horse → put a buff counter on CARDNAME |
| War Marshal | `dlvr8wunhg` | Class Bonus: Steadfast; Class Bonus Equestrian: as long as you control a Horse → +1 POWER and +1 LIFE |
| Yunzhou Cavalry | `ann23jkuys` | Class Bonus: Ranged 2; On Enter: if you control a Horse → CARDNAME becomes distant |
| Ritai Guard | `jbc30d18ys` | Class Bonus Equestrian: as long as you control a Horse → gains Taunt and Vigor |
| Poised Rearguard | `qso7cbzrky` | Hindered; Class Bonus On Enter: if you control a Horse → wake up CARDNAME |
| Shu Frontliner | `uhaao91ee1` | Costs 1 less while you control a Horse; Class Bonus: Floating Memory |
| Nanyue Portsman | `v5ppxyu1jm` | Gets +1 POWER while you control a Horse; Class Bonus: Floating Memory |
| Cao Cao, Aspirant of Chaos | `d5og6z31q9` | Unique; costs 3 less while you control a Horse; Class Bonus On Attack: banish a floating-memory GY card → deal 2 damage to each rested unit you don't control |

---

### 4. Empower Engine

AMB introduces several items and allies that grant **Empower** and several spells that branch on whether they are in an empowered state at resolution. Once the Empower system is confirmed working, these follow repeatable patterns.

**Key patterns:**
- **Grant-empower items**: REST-activated Regalias or sacrificeable items that set the empower counter before the next Spell resolves. (Fractal of Mana, Tome of Sorcery, Mnemonic Charm)
- **Grant-empower allies**: On-Enter or On-Attack effects that apply Empower. (Apprentice Aeromancer, Rainweaver Mage, Cloudstone Orb)
- **Payoff spells**: Cards with a branching resolution when empowered. Nascent Blast enters memory instead of GY; Leeching Bolt enters the material deck preserved instead of GY.
- **Preserve synergy**: Sempiternal Sage uses the **Preserve** keyword (dies → enters material deck preserved) and its On-Attack offers a choice of Recover 3 or Empower 3.

| Card | ID | Role |
|------|----|------|
| Fractal of Mana | `szeb8zzj86` | NORM Phantasia Fractal; Reservable; Class Bonus REST: Empower 1 |
| Tome of Sorcery | `sq0ou8vas3` | NORM Regalia Book; Class Bonus L2+ On Enter: draw into memory; REST: Empower 1 |
| Mnemonic Charm | `to1pmvo54d` | NORM Non-Regalia Scripture; On Enter: draw into memory; Class Bonus Sacrifice: Empower 2 |
| Cloudstone Orb | `ygqehvpblj` | Wind Regalia Bauble; On Enter: Empower X where X = wind non-champion objects you control; Class Bonus (3): return CARDNAME to its owner's material deck |
| Apprentice Aeromancer | `9f0nsj62l6` | Wind Cleric/Mage Ally; Class Bonus On Enter: Empower 2; Class Bonus: whenever you activate a wind Spell → CARDNAME gets +1 POWER until EOT |
| Rainweaver Mage | `qb6zhphtw6` | Water Mage Ally; Class Bonus On Enter: banish a floating-memory GY card → Empower 4 |
| Nascent Blast | `vajycopxgf` | NORM Cleric/Mage Spell; deal 3 to target unit; if empowered: put CARDNAME into your memory instead |
| Leeching Bolt | `hs1mzjzexc` | Tera Mage Spell; deal LV damage to target unit; Recover 2; Class Bonus: if empowered → put CARDNAME preserved into material deck |
| Sempiternal Sage | `zmoegdo111` | Tera Cleric/Mage Ally; Preserve (dies → material deck preserved); Class Bonus On Attack: choose either Recover 3 or Empower 3 |

---

---

## Isolated Cards

Cards with no strong mechanical dependency on other unimplemented cards. Bucketed by implementation effort.

---

### Easy

Simple On-Enter triggers, standard keyword applications, single-condition checks, or GY activation gates.

| Card | ID | Effect Summary |
|------|----|----------------|
| Brash Defender | `i1sh9r9rda` | NORM Guardian Ally; Level 1+: Vigor; Retort 3 |
| Invective Instruction | `smr2rn78qo` | NORM Tamer Skill; target ally gets +3 POWER until EOT; Class Bonus: if you control a non-Human ally → draw into memory |
| Palace Guard | `k940jhff6v` | NORM Guardian Human Ally; Taunt; Class Bonus: Retort 2 |
| Sword Saint of Everflame | `lpy7ie4v8n` | Fire Warrior Human Ally; Class Bonus: (2), banish this from GY → target fire weapon or ally gets +2 POWER until EOT |
| Set Ablaze | `d4z3tj2nu8` | Fire Mage Spell; deal 4 to target ally; Class Bonus Level 3+: deal 3 to each champion controlled by the same player as that ally |
| Tidal Tirade | `kyhl7zy5yj` | Water Guardian/Warrior Reaction Skill; target Human ally gets +1 LIFE and Retort 2 until EOT; Class Bonus: Floating Memory |
| Acolyte of Cultivation | `nsowyyn6jt` | NORM Cleric/Mage Ally; Class Bonus: as long as you've activated a Spell card this turn, costs 3 less |
| Fan of Insight | `sz1ty7vq6z` | NORM Regalia Fan; Class Bonus L2+ On Enter: draw into memory; Banish CARDNAME: return a card from your memory to hand |
| Rippleback Terrapin | `srkomr8ght` | Water Tamer Animal/Turtle Ally; Class Bonus: Spellshroud; On Enter: banish a floating-memory GY card → put a buff counter on CARDNAME and draw a card |
| Eventide Spear | `xjkdokzfd9` | Water Regalia Polearm Weapon; Class Bonus: as long as an opponent controls two or more rested units, you may activate this card from your material deck |

---

### Medium

Multiple conditions, zone-state lookups, cross-turn delayed triggers, tracking state tied to a specific object, or mechanics that require moderate engine hooks.

| Card | ID | Effect Summary |
|------|----|----------------|
| Crimson Prescience | `0dsdojl6l3` | Exia Warrior Reaction Skill; Class Bonus Damage 25+: costs 1 less; choose a card name — until EOT, prevent all damage dealt to your champion by sources with that name |
| Guan Yu, Prime Exemplar | `0oyxjld8jh` | Unique Wind Guardian Human Ally; as long as a Human ally you controlled has died this turn: costs 2 less and has Fast Activation; Ambush; Retort 2 |
| Illuminating Charge | `6ddfdn8y9f` | Luxem Tamer Skill; reveal all cards in your memory; put up to two Animal cards from among them directly onto the field (no cost paid) |
| Sun Quan, Sealbearer | `c5hgwip1ik` | Unique NORM Tamer/Warrior Ally; Level 1+ On Enter: put a buff counter on another target ally; Level 2+ passive: allies you control with a buff counter lose Pride |
| Conduit of Bloodfire | `epwl2vqikh` | Exia Mage Human Ally; On Enter: remove all damage counters from each champion your opponents control; for every four counters removed this way, put a buff counter on CARDNAME |
| Bathe in Light | `d9zax2g20h` | Luxem Cleric Spell; Recover 4; at the beginning of your next recollection phase: Recover 4 (delayed recollection-phase trigger) |
| Ingress of Sanguine Ire | `dfchplzf6m` | Exia Warrior Skill; activate only during an opponent's end phase; your champion's first attack during your next turn gets +3 POWER; if your champion hasn't taken damage this turn, draw two cards into memory (cross-turn stored buff consumed on first attack) |
| Guandu, Theater of War | `95ynk6lmnf` | Unique NORM Ranger Domain Castle; whenever you declare an attack with an ally: put a battle counter + Glimpse 1; 3rd resolution this turn: draw into memory; Upkeep: remove 2 battle counters at start of recollection phase or sacrifice |
| Sudden Snow | `dxAEI20h8F` | Water Mage Spell; optionally banish a floating-memory GY card → draw a card; until EOT, allies enter the field rested (global "enter-rested" state flag for the turn) |
| Fervent Lancer | `aws20fsihd` | Exia Warrior Ally; whenever you activate an exia element card, may banish it as it resolves; as long as a card is banished by CARDNAME: it gets +2 POWER and must attack a champion each turn if able (requires per-object "banished by this card" tracking) |
| Bloodbond Bladesworn | `blyb6fd6vy` | Exia Warrior Ally; Class Bonus: gets +1 POWER for every ten damage counters on your champion; whenever CARDNAME is dealt damage, deal that much damage to your champion (self-redirect on damage received) |
| Molten Arrow | `mvfcd0ukk6` | Fire Ranger Arrow Item; REST: load CARDNAME into target unloaded Bow; Banish three fire GY cards: load CARDNAME from your graveyard into target unloaded Bow (loading from GY rather than hand) |
| Ghostsight Glass | `cc0jmpmman` | NORM Regalia Cleric Accessory; (3) REST (slow speed only): target unit gains True Sight until EOT |
| Everflame Staff | `nrvth9vyz1` | Fire Regalia Staff; whenever a fire Spell source you control deals damage → put a refinement counter on CARDNAME; Class Bonus: Banish CARDNAME (requires 3+ counters) → as a Spell, deal 4 to target champion |
| Adept Swordmaster | `txgvf6xpkq` | Wind Warrior Ally; Intercept; Class Bonus passive: all weapons you control get +1 POWER (continuous global weapon stat modifier) |
| Burst Asunder | `rzsr6aw4hz` | Water Cleric/Mage Spell; Class Bonus: costs 2 less; deal 2 to target unit; then sacrifice any number of Fractals — deal 2 additional damage per Fractal sacrificed (multi-select Fractal sacrifice loop) |
| Tidal Lock | `c4poa10ezw` | Water Cleric Reaction Spell; costs 2 less while you have 3+ water GY cards; Negate target activation unless controller pays (2); banish the negated card *(depends on Negate activation logic being in engine)* |
| Spellshield: Tera | `yunjm0of8e` | Tera Mage Reaction Spell; Class Bonus: costs 1 less; prevent the next damage dealt to your champion this turn; reveal cards from the top of your deck equal to the prevented amount → put them preserved into your material deck |

---

### Hard

Permanent rule replacements, copy mechanics injected into existing intent, per-attack dynamic payment gates, damage-doubling replacement hooks, face-down state in the banishment zone, or injecting runtime On-Enter effects onto specific card instances.

| Card | ID | Effect Summary |
|------|----|----------------|
| Slice and Dice | `3jg01o26b4` | NORM Assassin Dagger Attack 3; Prepare 3; On Hit if prepared: the attacker declares an additional attack and a copy of CARDNAME is placed in that attacker's intent — the copy is non-prepared and gets +3 POWER (requires creating and injecting a modified card copy directly into an in-progress intent) |
| Seize Fate | `l61ubi93jx` | Exia Warrior Skill; Jin Bonus: costs 2 less; for the rest of the game: if your champion would take damage, remove that many damage counters from them instead; if damage counters reach zero this way, banish your champion (permanent replacement of the standard champion damage model — rewrites champion damage resolution for the duration of the game) |
| Oppressive Presence | `j9hjjvkyyr` | Fire Warrior Skill; activate only during an opponent's recollection phase; until EOT: each time a player would declare an attack with an ally, they must first pay (X) where X = the highest POWER among fire element allies you control (dynamic per-attack-declaration payment gate that recomputes X each trigger) |
| Proof of Life | `mes4idoihs` | Exia Warrior Skill; the next time your champion would take damage this turn, double that damage; Damage 40+: (2), banish this from GY → wake up your champion (one-shot damage-doubling replacement hook on the next champion damage event) |
| Orb of Sealing | `mekutzp19y` | NORM Regalia Bauble; REST: put a seal counter on up to two target face-up non-champion non-regalia cards in a single banishment and turn them face down; On Leave: remove all seal counters from all cards in each banishment, turn affected cards face up (introduces face-down objects in the banishment zone) |
| Vengeful Gust | `q4dvnn3zp1` | Wind Mage Spell; Suppress target ally; Level 3+: the next time that specific suppressed card would enter the field this turn, it enters with an injected On-Enter effect: "deal 4 damage to your champion" (requires attaching a runtime On-Enter effect to a specific card instance that persists through the suppression re-entry window) |
| Yudi, Gossamer Jade | `l94wp7qjwb` | Unique Tera Cleric/Mage Phantasia; whenever you empower an amount, if that amount is greater than the current number of root counters on CARDNAME, put a root counter on it (tracks the running maximum single-empower value); Class Bonus: players cannot declare attacks with non-tera element units unless they pay (X) per declaration, where X = root counter count on CARDNAME |
| Desperate Cavalier | `slmer06rku` | Exia Warrior Ally; Class Bonus On Attack: if your influence is four or less, banish the top two cards of your deck; you may activate those specific banished cards, with an additional cost of 2 unpreventable damage to your champion per activation (temporary tagged activation window for two specific banished card objects) |
