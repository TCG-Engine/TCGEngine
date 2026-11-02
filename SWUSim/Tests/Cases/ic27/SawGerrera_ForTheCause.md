# OnAttack_DoublesAnotherFriendlyUnit
#// IC27_126 Saw Gerrera — For the Cause (6/5 Ground, Aggression/Aggression, Rebel, unique)
#// "On Attack: You may double another friendly unit's power for this phase."
#// An optional pick over the OTHER friendly units (team units in Team Suns). Doubling is a MULTIPLICATIVE
#// modifier (CR 15.2/15.3): _SWUPowerMultiplier applies it after every +/- and on every read, so later buffs,
#// Raid and attack bonuses are doubled too; each application stacks (×4 for two). Phase duration.
#//
#// COVERAGE: positive=OnAttack_DoublesAnotherFriendlyUnit ·
#//           offer=Offer_OtherFriendlyUnitsOnly (Saw and the enemy unit excluded; space friendly in) ·
#//           decline=Decline_PowerUnchanged ·
#//           no-target=NoOtherFriendlyUnit_NoPrompt ·
#//           quantity=DoublesTheModifiedPower (3+1 → 8, not 3×2+1) + ZeroPowerStaysZero ·
#//           multiplicative-last=LaterBuffIsDoubledToo (live recompute, CR 15.3) + RaidIsAddedBeforeDoubling ·
#//           combat=DoubledUnitAttacks_DealsDoubled + DoubledDefender_CounterDamageDoubled ·
#//           stacking+control=StolenThenDoubledAgain_IsQuadrupled (marker survives Change of Heart; two tokens) ·
#//           duration=ExpiresAtTheEndOfThePhase ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=Team Suns ("friendly") — TeamSuns_TeammateUnitIsOffered + TeamSuns_DoublesTeammateUnit

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:0:POWER:6
P2BASEDMG:6
P1NODECISION

---

# Offer_OtherFriendlyUnitsOnly
#// Offered: P1's Marine and P1's X-Wing (space). Not Saw himself ("another"), not P2's Marine ("friendly").

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0

---

# Decline_PowerUnchanged

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P2BASEDMG:6
P1NODECISION

---

# NoOtherFriendlyUnit_NoPrompt
#// Only Saw on P1's side (an enemy unit does not count): no prompt, the attack goes through.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_126:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:6
P2GROUNDARENAUNIT:0:POWER:3

---

# DoublesTheModifiedPower
#// The Marine carries an Experience token (3+1 = 4): doubled to 8 — the modified value, not the printed one
#// (a printed-power reading gives 3×2+1 = 7).

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:8
P1GROUNDARENAUNIT:1:HP:4

---

# ZeroPowerStaysZero
#// A 0-power Cantina Braggart doubled is still 0 outside an attack.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_157:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:0

---

# LaterBuffIsDoubledToo
#// CR 15.3: the value is recalculated whenever a modifier is added. After the double, Academy Training (+2/+2;
#// Command off-aspect → 4 resources) goes on the Marine: (3+2)×2 = 10. A "+3 snapshot" reading gives 6+2 = 8.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]
WithP1Hand: SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:10
P1GROUNDARENAUNIT:1:HP:5

---

# RaidIsAddedBeforeDoubling
#// The doubled Cantina Braggart (0 power, Raid 2) attacks the base: (0+2)×2 = 4. Doubling first and adding Raid
#// after would deal 2. Total base damage 6 (Saw) + 4.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_157:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:10

---

# DoubledUnitAttacks_DealsDoubled
#// P1's doubled Consular Security Force (3/7 → 6) attacks P2's Consular Security Force (3/7): 6 damage, and it
#// takes 3 back.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# DoubledDefender_CounterDamageDoubled
#// The double holds on defence: P2's Consular Security Force (3/7) attacks P1's doubled one (6) and takes 6.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithP1GroundArena: [IC27_126:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P2>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# StolenThenDoubledAgain_IsQuadrupled
#// P1's Saw doubles P1's Marine (6). P2 takes it with Change of Heart — the double rides the unit (still 6 under
#// P2). P2's own Saw then attacks and doubles it again: two tokens, ×4 = 12. A de-duplicated token would read 6;
#// a marker lost on the control change would read 6.

## GIVEN
CommonSetup: rrw/yyk/{theirResources:6}
SkipPreGame: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]
WithP2GroundArena: IC27_126:1:0
WithP2Hand: SOR_224

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:POWER:12
P1GROUNDARENACOUNT:1

---

# ExpiresAtTheEndOfThePhase
#// "For this phase": after the regroup phase the Marine is back to 3.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:1:POWER:3

---

# TeamSuns_TeammateUnitIsOffered
#// "friendly" in Team Suns (P1+P3 vs P2+P4): P1's Marine and P3's Marine are offered; P2's and P4's are not.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:P2B

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-1&p3GroundArena-0

---

# TeamSuns_DoublesTeammateUnit

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_126:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:POWER:3
P4GROUNDARENAUNIT:0:POWER:3

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_126:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1NODECISION
