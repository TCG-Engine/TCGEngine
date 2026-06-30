# LOF_215 (non-Vehicle restriction) with only a Vehicle in play — no valid targets, stays in hand.
# LOF_215 is Cunning aspect, cost 2. Thrawn+yellow base (yyk) covers Cunning → no penalty. 2 resources.
# SOR_244 (Snowspeeder) has Vehicle trait → NOT a valid target → upgrade stays in hand.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:LOF_215}
WithP1GroundArena: SOR_244:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
