# JTL_172 (Vehicle-only restriction) attaches to Vehicle SOR_244 — succeeds.
# JTL_172 is Aggression aspect, cost 2. Sabine (grw) covers Aggression → no penalty. 2 resources.
# SOR_244 (Snowspeeder) has Vehicle trait → valid target.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:JTL_172}
WithP1GroundArena: SOR_244:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_172
