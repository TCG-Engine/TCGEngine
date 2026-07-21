# PlayUnit_UseForce_Buff
#// LOF_087 Eighth Brother (5/7) — "When you play another unit: you may use the Force → give a unit +2/+2."
#// With Eighth Brother in play and the Force, P1 plays another unit; the reaction lets P1 use the Force and
#// buff Eighth Brother himself (5 → 7 power).

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:POWER:7

---

# Buff_EnemyUnit
#// LOF_087 Eighth Brother — the +2/+2 may target ANY unit, including an enemy. P1 plays SEC_080, uses the
#// Force, and buffs the enemy Death Star Stormtrooper (SOR_128, 3/1 → 5/3). (FT: "should give an enemy unit
#// +2/+2".)

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_087:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:3

---

# Buff_FriendlyOtherUnit
#// LOF_087 Eighth Brother — the +2/+2 may target another friendly unit. With Battlefield Marine (SOR_095,
#// 3/3) already in play, P1 plays SEC_080, uses the Force, and buffs the Marine (→ 5/5). (FT: "should give a
#// friendly unit +2/+2".)

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_087:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5

---

# Decline_KeepForce_NoBuff
#// LOF_087 Eighth Brother — the reaction is a MAY. Declining keeps the Force token and gives no +2/+2. P1
#// plays SEC_080 and declines; the Force is retained and no unit is buffed. (FT: "should not be triggered as
#// player decides not to use the Force".)

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:3

---

# NoForce_NoTrigger
#// LOF_087 Eighth Brother — with no Force token the reaction cannot fire at all: no prompt, no buff. P1 plays
#// SEC_080 without the Force; the turn simply proceeds with no decision. (FT: "should not be triggered as
#// player doesn't have the Force".)

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: LOF_087:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1NODECISION
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:3
