# LOF_215 (non-Vehicle restriction) attaches to non-Vehicle SOR_095 — succeeds.
# LOF_215 is Cunning aspect, cost 2. Thrawn+yellow base (yyk) covers Cunning → no penalty. 2 resources.
# SOR_095 (Battlefield Marine) has no Vehicle trait → valid target.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:LOF_215}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LOF_215
