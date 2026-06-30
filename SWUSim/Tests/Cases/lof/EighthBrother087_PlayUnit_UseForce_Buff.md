# LOF_087 Eighth Brother (5/7) — "When you play another unit: you may use the Force → give a unit +2/+2."
# With Eighth Brother in play and the Force, P1 plays another unit; the reaction lets P1 use the Force and
# buff Eighth Brother himself (5 → 7 power).

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
