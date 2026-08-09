# DealsThreeToAUnit
#// IBH_061 We're In Trouble (Event, cost 3, Aggression) — Deal 3 damage to a unit. Enemy 4/7 takes 3
#//   (survives). Single unit on board → target auto-resolves.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_061
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Reprint086
#// IBH_086 We're In Trouble (reprint of IBH_061) — deal 3 to a unit. Confirms the duplicate is wired.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_086
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# CanDamageAFRIENDLYUnit
#// IBH_061 We're In Trouble — "deal 3 damage to a unit" is unqualified, so a FRIENDLY unit is a legal
#// target. Both a friendly and an enemy body are on board and the friendly one is chosen; it takes the
#// full 3 while the enemy is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_061
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# ShieldedTarget_ShieldAbsorbsItAll_NoDamageMarked
#// IBH_061 We're In Trouble — a Shield token absorbs the whole 3-damage hit: the shield is defeated and
#// the unit ends with ZERO damage marked (a shield absorbs the damage event, it does not merely reduce
#// it). Upgrade count 1 -> 0 and damage stays 0.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_061
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
