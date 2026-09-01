# BuffsOtherRebels
#// SOR_242 Massassi Group Commander / General Dodonna (4/4, Rebel) —
#// "Other friendly Rebel units get +1/+1." The OTHER Rebel unit (Consular Security
#// Force SOR_046, 3/7) reads 4/8; Dodonna himself is excluded ("other") → stays 4/4.
#// COVERAGE: offer=N/A (a continuous aura — no decision, no target, nothing is ever offered) ·
#//           decline=N/A (no "you may"; the buff is not optional and cannot be refused) ·
#//           control=StolenRebelUnit_IsFriendlyByCONTROL_NotByOwnership (friendly is read from the
#//           controller, not the owner) + EnemyRebelUnit_GetsNothing (the negative half) ·
#//           boundary pair=Control_AuraKeepsTheDamagedRebelAlive (3 damage vs 4 HP survives) paired
#//           with AuraStopsWhenDodonnaLeavesPlay_HPDropDefeatsTheDamagedRebel (3 damage vs 3 HP is
#//           lethal) — currently RED, see that section ·
#//           reqboundary=N/A (nothing is stored: SWUTraitCommanderBonus recomputes the bonus from the
#//           live arena on every power/HP read, so no value survives — or fails to survive — a
#//           serialization round trip)

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0    # General Dodonna (4/4, Rebel) — index 0
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7, Rebel) — index 1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:8
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# NonRebelFriendlyUnit_GetsNothing
#// Intended: the aura reads "other friendly REBEL units". The trait gate is load-bearing — a friendly
#// non-Rebel unit must be untouched. SOR_207 Crafty Smuggler (Underworld, 2/2) sits beside Dodonna and
#// still reads 2/2, while the Rebel Consular Security Force (3/7) beside it reads 4/8. Without the
#// Underworld control, an implementation that buffed EVERY friendly unit would pass the positive test.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0    # General Dodonna — index 0
WithP1GroundArena: SOR_207:1:0    # Crafty Smuggler (Underworld, 2/2) — index 1, NOT Rebel
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (Rebel, 3/7) — index 2

## EXPECT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:2:POWER:4
P1GROUNDARENAUNIT:2:HP:8

---

# EnemyRebelUnit_GetsNothing
#// Intended: "other FRIENDLY Rebel units" — controller-scoped. An ENEMY Rebel unit with the exact same
#// trait line is the other load-bearing negative: P2's Battlefield Marine (Rebel/Trooper, 3/3) stays
#// 3/3 while P1's identical Battlefield Marine reads 4/4. Same card, both arenas, opposite results —
#// so the difference can only be the controller check.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0    # General Dodonna — index 0
WithP1GroundArena: SOR_095:1:0    # friendly Battlefield Marine (Rebel) — index 1
WithP2GroundArena: SOR_095:1:0    # ENEMY Battlefield Marine (Rebel) — index 0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# FriendlyRebelSpaceUnit_AlsoBuffed
#// Intended: the aura names no ARENA — "other friendly Rebel units" spans the whole board, not just
#// Dodonna's ground arena. The friendly Alliance X-Wing (SOR_237, Rebel/Vehicle/Fighter, 2/3) in SPACE
#// reads 3/4 even though Dodonna is a ground unit. The enemy X-Wing in the same space arena stays 2/3,
#// keeping the friendly gate honest across the arena boundary too.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0    # General Dodonna (Ground)
WithP1SpaceArena: SOR_237:1:0     # friendly Alliance X-Wing (Rebel, 2/3)
WithP2SpaceArena: SOR_237:1:0     # enemy Alliance X-Wing (Rebel, 2/3)

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P2SPACEARENAUNIT:0:POWER:2
P2SPACEARENAUNIT:0:HP:3

---

# DeployedRebelLeaderUnit_IsAUnitAndIsBuffed
#// Intended: a deployed leader is a UNIT in the arena, so a deployed leader whose traits include Rebel
#// is an "other friendly Rebel unit" and gets +1/+1. Leia Organa (SOR_009, Rebel/Official) deployed
#// reads 3/6 base → 4/7 beside Dodonna. This is the dispatch path a bare-CardID trait test most often
#// gets wrong, and the deployed leader seats AFTER the plain arena lines, so it is index 1.

## GIVEN
CommonSetup: ggw/ggw/{myLeaderDeployed:true}
WithP1GroundArena: SOR_242:1:0    # General Dodonna — index 0

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# Control_AuraKeepsTheDamagedRebelAlive
#// The PASSING control for the section below: with Dodonna in play the Battlefield Marine (3/3) reads
#// 4 HP, so 3 damage is survivable and both P1 units are on the board. This proves the fixture seats a
#// legally-alive 3-damage Marine and that its survival is owed to Dodonna's +1/+1 — nothing else.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_242:1:0    # General Dodonna 4/4
WithP1GroundArena: SOR_095:1:3    # Battlefield Marine 3/3 with 3 damage — alive only at 4 HP
WithP2GroundArena: LAW_124:1:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# AuraStopsWhenDodonnaLeavesPlay_HPDropDefeatsTheDamagedRebel
#// Intended (CR state check — a unit with 0 or less remaining HP is defeated): the +1/+1 is a
#// continuous ability of an in-play source, so it ends the instant Dodonna is defeated. The friendly
#// Battlefield Marine (3/3) carries exactly 3 damage — alive at 4 HP while Dodonna is in play (see
#// Control_AuraKeepsTheDamagedRebelAlive above), lethally damaged the moment the aura falls away.
#// Dodonna (4/4) attacks LAW_124 Industrious Team (4/7) and dies to the 4 counter-damage, so BOTH P1
#// units must end in the discard. This is the N vs N-1 boundary of the HP half of the buff: one HP
#// less than the aura provides is lethal.
#// ⚠ RED — engine reports P1GROUNDARENACOUNT 1 / P1DISCARDCOUNT 1: the Marine stays on the board at
#// HP 3 with DAMAGE 3. The state-based "no remaining HP" sweep is not run after a UNIT leaves play,
#// only after an UPGRADE is removed, so losing an aura source never defeats a unit it was keeping
#// alive. Reproduces identically when Dodonna is destroyed by an ability instead of in combat.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_242:1:0    # General Dodonna 4/4 — index 0
WithP1GroundArena: SOR_095:1:3    # Battlefield Marine 3/3 with 3 damage — alive only at 4 HP
WithP2GroundArena: LAW_124:1:0    # Industrious Team 4/7 — kills Dodonna on the counter-swing

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# StolenRebelUnit_IsFriendlyByCONTROL_NotByOwnership
#// Intended: "other FRIENDLY Rebel units" is decided by CONTROL, not ownership. P1 controls a
#// Battlefield Marine (Rebel) that P2 still OWNS — it is friendly to Dodonna and reads 4/4, exactly
#// like a P1-owned copy. The P2-controlled copy of the same card in P2's arena stays 3/3, so the two
#// halves of the reading are separated: the owner is identical, only the controller differs.
#// (A `Controlled` unit seats AFTER every plain WithP1GroundArena line, so it is index 1.)

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0              # General Dodonna — index 0
WithP1GroundArenaControlled: SOR_095:2      # P1 CONTROLS it, P2 OWNS it — index 1
WithP2GroundArena: SOR_095:1:0              # P2 controls + owns an identical Marine

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
