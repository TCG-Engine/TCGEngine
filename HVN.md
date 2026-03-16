# HVN — Unimplemented Cards Analysis

**Total unimplemented: 91 cards**

---

## Mechanical Clusters

Cards that share a core mechanic and should ideally be implemented together.

---

### 3. Guo Jia Lineage — Quest Counters

Guo Jia champions accumulate **quest counters** on themselves. Several cards allow removing a quest counter as an alternate payment. `Guo Jia, Blessed Scion` also adds a **Lineage Release** — a negate ability fired in response to activations targeting Fatestone or Fatebound objects.

| Card | ID | Type | Effect Summary |
|------|----|------|----------------|
| Guo Jia, Chosen Disciple | `j6dkdoxyqt` | L1 Champion | On Enter: if no Fatestone regalia, reveal material deck; put cheapest Fatestone regalia onto field |
| Guo Jia, Blessed Scion | `59ipqa91r2` | L2 Champion | On Enter: put 2 quest counters OR draw; Lineage Release: negate activation/trigger targeting a Fatestone or Fatebound you control |
| Guo Jia, Heaven's Favored | `enxi6tshtu` | L3 Champion | Costs 3 less to materialize while you control a Shenju ally; On Enter: 3 quest counters OR recover 3 |
| Bell of the Chosen | `dvxsl5klqe` | Regalia Accessory | On Enter: quest counter on champion; if entered from banishment: glimpse 2 |
| Searing Truth | `pfstbz0i63` | Spell | Deal 2 damage to target unit; Guo Jia Bonus: quest counter on champion |
| Clash of Fates | `9rbziyasag` | Skill | Guo Jia Bonus: remove quest counter instead of paying; put buff counter on Fatestone/Fatebound; if that object is a Shenju ally, it gains vigor until EOT |
| Whirlwind Threads | `p7nkdqnzzg` | Spell | Put quest counter on champion; if any object was suppressed this turn, put into memory |

---

### 4. Fatestone Objects & Fatebound Transforms

These items all have the FATESTONE subtype and most include a `[Guo Jia Bonus]` transform trigger that converts them into a Fatebound object. **Transform** replaces the current field object with a new card (the Fatebound form) at the same position; the specific Fatebound card each Fatestone becomes must be sourced from the database. Transform conditions vary — REST + cost, triggered, end-phase passive. Several Fatestones also have useful non-Guo-Jia effects that work independently.

| Card | ID | Type | Notable Mechanic |
|------|----|------|-----------------|
| Fatestone of Progress | `2sn7hlyrkw` | Fatestone | Guo Jia Bonus On Enter: quest on champion; (4) activate: transform |
| Fluvial Fatestone | `3h93tgm72l` | Fatestone | Fast Activation; On Enter: target ally +2 life; Guo Jia Bonus — (4) REST + mill 2: transform |
| Cyclonic Fatestone | `l6410a85dn` | Fatestone | Fast Activation; On Enter: suppress attacking ally you don't control; Guo Jia Bonus — (3) REST: transform |
| Idle Fatestone | `qiv63tpshe` | Fatestone | On Enter: look at top 4; Guo Jia Bonus — REST (2): reveal top card; if reserve cost is even → buff counter on this, else → transform |
| Pelagic Fatestone | `tqkkyf4ktr` | Fatestone | On Enter: draw into memory; Floating Memory; Guo Jia Bonus: if banished from gy to pay a memory cost → enters field transformed |
| Fatestone of Balance | `v4gtq1ibth` | Fatestone | Guo Jia Bonus: whenever opponent activates and has exactly 3 cards in memory → transform |
| Wildgrowth Fatestone | `x2oydmfcre` | Fatestone | Guo Jia Bonus: whenever another wind element object enters field under your control → buff counter; at 6+ buff counters may transform |
| Beseeched Fatestone | `x7t0vki9gy` | Fatestone | On Enter: materialize; Guo Jia Bonus — (6) REST: transform, costs 2 less per materialize this turn |
| Submerged Fatestone | `zfb0pzm6qp` | Fatestone | Opponents' champions −1 level (permanent passive); Guo Jia Bonus: at beginning of your recollection phase, may banish floating memory from gy → transform |
| Coiled Fatestone | `ulh4lplwqe` | Fatestone | On Enter: discard 2 at random, draw for each fire discarded; Guo Jia Bonus — REST (1): deal 1 to each champion, add age counter; transform at 3 age counters |
| Tidefate Brooch | `vubaywkr69` | Regalia Accessory | End phase: add X refinement counters (X = Fatestone/Fatebound count); (3) banish: if 10+ refinement counters, mill top 10 |
| Think Deep | `xw9w6y7vtz` | Spell | Costs 2 less while you control a Fatestone or Fatebound; glimpse 2 then put up to 2 from top of deck into gy |
| Floating Peace | `0s4xe169m2` | Spell | Recover 1+X (X = Fatestone/Fatebound count); Floating Memory |
| Winds of Destiny | `nhk5d19n82` | Spell | May rest two Fatestones instead of paying cost; suppress target ally, item, or weapon |

*`Pelagic Fatestone`'s transform-on-memory-banish triggers during payment — requires a hook in the floating memory banish logic. `Beseeched Fatestone`'s cost reduction counts materializations this turn, requiring a per-turn materialize counter. `Fatestone of Balance` watches the opponent's current memory count mid-activation.*

---

### 7. Kindle — Fire Graveyard Payment

Cards using or granting **Kindle N** (banish up to N fire element cards from graveyard as you activate; each one pays 1 cost).

| Card | ID | Type | Note |
|------|----|------|------|
| Silent Firebrand | `vwktc1c3kn` | Ally Assassin 4/1 | Kindle 2; Class Bonus |
| Dazzling Courtesan | `znk6g5o8ys` | Ally Mage 2/2 | Kindle 3 |
| Tinderflare Pivot | `s3bqtjayfn` | Skill | Kindle 2 + distant effect *(see Distant cluster)* |
| Glowering Conflagration | `1ym2py8u7q` | Spell | Kindle 3 + X damage *(see Diao Chan cluster)* |
| Ritai Stablemaster | `ba0tqvwlp1` | Ally Tamer 1/2 | Equestrian; On Enter with Horse ally: discard up to 2 fire cards → draw into memory for each; Horse cards you activate gain Kindle 3 |
| Lu Xun, Pyre Strategist | `xllhbjr20n` | Unique Ally Mage 0/3 | Kindle 3; On Enter: enlighten counter on champion; Class Bonus: whenever enlighten counters are removed from champion, may rest Lu Xun + empower 3 |
| Jianye, Dawn's Keep | `4ms1r3hjxp` | Domain *(see Siegeable cluster)* | Kindle 6 |

*`Ritai Stablemaster`'s "Horse cards you activate gain Kindle 3" is a dynamic cost hook applied to any card with the Horse subtype the player activates while Stablemaster is on the field — similar to class-bonus cost reductions but keyed on subtype.*

---

### 8. Shifting Currents (Kongming)

Kongming's mechanic tracks a compass direction (North / East / South / West) on the Shifting Currents zone artifact. Cards check or respond to the current facing.

| Card | ID | Type | Effect Summary |
|------|----|------|----------------|
| Kongming, Erudite Strategist | `0i139x5eub` | L2 Champion | On Enter: banish top card while facing each of the 4 compass directions; until beginning of next turn may play each banished card while Shifting Currents faces that direction |
| Bagua of Vital Demise | `imdj3c7oh0` | Spell | Shifting Currents faces West: may activate from material deck; deal 4 to target unit; if faces East: return to material deck preserved |
| Dynasty Chancellor | `do1blsupu0` | Ally | On Enter if Shifting Currents faces North: mill 2 *(also in Deluge cluster)* |

*Kongming's On Enter creates four paired (direction → banished card) associations. The "may play" window for each card is conditional on the compass current matching that card's direction at activation time. This multi-slot deferred play window spanning until the next turn is the primary complexity.*

---

### 9. Arcane Shenju Interactions

Cards from the arcane element that can have their element requirement bypassed while you control an arcane element Shenju ally.

| Card | ID | Type | Effect Summary |
|------|----|------|----------------|
| Harness Lightning | `bzwj7ztr78` | Skill Arcane | Arcane Shenju bypass; choose: Empower 4 + banish this, OR target ally +4 power + banish this |
| Seiryuu's Command | `v9d2242357` | Skill Arcane | Arcane Shenju bypass; until EOT, whenever target Beast attacks, trigger all of its On Attack abilities twice |
| Clash of Fates | `9rbziyasag` | Skill | *(see Guo Jia cluster)* if Shenju ally, Fatestone/Fatebound gains vigor |

*`Seiryuu's Command`'s "trigger On Attack abilities twice" requires a hook in the On Attack event that duplicates the trigger dispatch for a specific targeted Beast for the remainder of the turn.*

---

### 10. Siegeable Domains

Domains with the Siegeable subtype can be attacked and track HP via **durability** counters. Also included is a spell that can explicitly target Siegeable domains.

| Card | ID | Type | Effect Summary |
|------|----|------|----------------|
| Baidi, Oathsworn Palace | `43rtqovkti` | Domain Wind | Siegeable Castle; Ranger units you control have Ranged 1 |
| Jianye, Dawn's Keep | `4ms1r3hjxp` | Domain Fire | Kindle 6; Class Bonus: fire element allies' On Death: if your influence is 6 or less, draw a card |
| Chibi, Battle of Red Cliffs | `881gacexpv` | Domain Norm | Siegeable River; players can't declare attacks with allies without paying 1 per attack declaration |
| Xuchang, Frozen Citadel | `xpb20rar4k` | Domain Water | Siegeable Castle; at beginning of each opponent's recollection phase: may banish a floating-memory card from their gy — if they don't, their next card activated this turn costs 2 more |
| Demolition | `7iak6hyh6b` | Spell Fire | Class Bonus costs 1 less; deal 3 damage to target unit **or** Siegeable domain |

*`Demolition` is the only damage spell that can target a domain structure — requires the targeting filter to include Siegeable domains as valid targets. `Chibi`'s ally-attack tax fires per attack declaration, requiring a pre-declaration payment gate. `Jianye`'s On-Death trigger fires for each fire ally and needs to count influence at that moment.*

---

### 11. Negate / Counter Package

Cards that hard-counter activations or suppress On Enter triggers.

| Card | ID | Type | Effect Summary |
|------|----|------|----------------|
| Annul Spell | `u817uqlk1j` | Reaction Spell | L2+ costs 1 less; negate target Spell activation unless controller pays 3 |
| Frostbitten Etui | `bdhjszsj2z` | Regalia Bauble | L2+: banish this → negate all On Enter triggers from target ally you don't control unless its controller pays 3 |
| Stifling Trap | `z5exbwdp7q` | Reaction Skill | Deal 2 to target ally + negate all On Enter triggers from that ally; Class Bonus: if it's not your turn and you have 2 preparation counters, activate from memory for free |

*`Stifling Trap`'s free-from-memory activation via preparation counters is an alternate cost that removes the counter-based payment from the champion at activation time.*

---

## Isolated Cards

Cards without strong mechanical dependency on other unimplemented cards.

---

### Easy

Straightforward effects using established patterns.

| Card | ID | Effect Summary |
|------|----|----------------|
| Disenchant | `zd83net7x0` | Destroy target phantasia |
| Sword Saint of Eveswind | `a7lr70xglo` | Class Bonus On Enter: if entered from a banishment, put 2 buff counters on it |
| Beguiling Bandit | `jyrqgyj9vn` | Players can't attack it without paying 1; Class Bonus + L2+: +1 power |
| Wingpeak Patriarch | `wov58exji1` | Other Bird objects you control get +1 power (field-presence passive) |
| Coronation Ceremony | `y4PZCiE26a` | Reaction; target unit gains spellshroud until EOT; costs 2 less when targeting a unique ally |

---

### Medium

Multiple triggers, conditional passives, novel resource interactions, Brew cost substitution, or moderate zone-state checks.

| Card | ID | Effect Summary |
|------|----|----------------|
| Bloodshroud Temper | `6vzr7yc6vi` | Target weapon gains omnishroud until EOT; Damage 20+ bonus: also put 2 durability counters on that weapon |
| Sunglory Sentinel | `a3v1ybmvpb` | Class Bonus Foster; while fostered and attacking a champion, gets +2 power |
| Scorching Imperilment | `aj7pz79wsp` | Phantasia; at beginning of each player's end phase, that player may discard a card — if they do, draw a card (symmetric per-player EOT trigger) |
| Immaterial Dissolution | `55d9w9uuvq` | L2+ costs 1 less; destroy up to 3 target non-regalia token objects whose combined reserve cost is 4 or less |
| Nullifying Mirror | `pol1nz0j1n` | REST: cards in target opponent's memory become norm element until EOT — requires element override on in-memory cards |
| Censer of Restful Peace | `0nlhgqpckq` | Global passive regalia: all cards in both graveyards lose all abilities — requires ability suppression for graveyard objects |
| Invigorating Concoction | `nsjukk5zk4` | Brew (One Flower + One Herb); Hindered; REST + Sacrifice: put 2 buff counters on target ally + draw |
| Explosive Concoction | `yorsltrnu3` | Brew (One Leaf + One Herb); Hindered; REST + Sacrifice: deal 4 damage to target champion + draw |
| Sword Saint's Vow | `r44lyrzo6o` | Regalia Weapon 0/—; +1 power per durability counter; Class Bonus: whenever you activate a Craft action, add 2 durability; On Hit: remove 1 durability |
| Shangxiang, Fierce Princess | `s2tzwv1uw3` | Unique Ally 2/3; Imbue 3; while imbued, prevent 2 damage from non-norm sources |
| Shattering Discharge | `uutqo9hm33` | Arcane Reaction; Class Bonus: put charge counter on this when banished from memory; activatable from banishment with a charge counter; deal 2 unpreventable damage to champion + banish this |
| Maiden of Shrouded Fog | `wum3f33kay` | Phantasia Ally 2/2; Class Bonus Spellshroud; whenever you activate a card from your memory, put a buff counter on target phantasia ally you control |
| Lu Xun, Pyre Strategist | `xllhbjr20n` | Unique Ally 0/3; Kindle 3; On Enter: enlighten counter on champion; Class Bonus: whenever enlighten counters are removed from your champion, may rest Lu Xun + empower 3 |

*`Invigorating Concoction` / `Explosive Concoction` require the **Brew** mechanic: alternate cost by sacrificing specified subtype tokens (Flower, Herb, Leaf). Confirm whether Brew is already implemented from prior sets. `Nullifying Mirror` needs runtime element overrides on memory-zone objects, similar to the Nullifying Lantern graveyard override pattern. `Censer of Restful Peace`'s "graveyard cards lose all abilities" affects On Death triggers and floating-memory abilities.*

---

### Hard

Replacement effects, stat ordering requirements, retargeting activations, named-card global locks, control theft with unusual conditions, global army restriction gates, or per-attack-intent power injection.

| Card | ID | Effect Summary |
|------|----|----------------|
| General at Arms | `9m72c8x9oh` | Class Bonus: Polearm attack cards you activate enter the intent with +2 power — requires hooking into the intent-entry path filtered by weapon subtype |
| Crystallized Destiny | `l36wwe3d5c` | Reaction; prevent all damage from the next time your champion would be dealt damage this turn; if 7+ damage was prevented, opponents' activated cards cost 2 more this turn — two-stage: one-shot total prevention replacement that tracks the prevented amount and conditionally applies a global activation tax |
| Revealing Mesmer | `l7pnn9jw7c` | Phantasia; (2) REST: all champions lose spellshroud until EOT; (2) REST: change the target of any activation or trigger that targets a phantasia you control to Revealing Mesmer — in-flight retargeting requires hooking the target resolution pipeline |
| Resonating Fugue | `optpu3fubb` | Class Bonus costs 2 less; switch the power and life of target Animal/Beast you control until EOT, applied *after* all other stat-modifying effects — requires last-in-chain ordering at stat evaluation time |
| Kingdom's Divide | `qy34r8gffr` | Choose a card name; until beginning of your next turn, cards with that name cost 2 more; L2+ Floating Memory — requires a card-name input UI and a global per-name activation cost hook |
| Da Qiao, Cinderbinder | `ugl6g5znia` | Unique Ally 0/4; allies with frenzy counters can't attack, can't intercept, and their activated abilities can't be activated; Class Bonus REST: put frenzy counter on ally OR empower X (X = frenzy allies on field) — requires compliance gates inserted into attack declaration, intercept declaration, and ability activation paths |
| Yuan Shao, Crown General | `x8o84m37ti` | Unique Ally 1/3; unique allies opponents control have pride 2 (dynamic passive injection); L2+ REST: gain control of target unique ally as a Spell (control theft); activate only if 3+ unique allies you don't control are on the field |
