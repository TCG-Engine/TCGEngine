# LAW_139 Admiral Motti (4/5) — Friendly leader units get +2/+2. Deploy Luke (4/7); with Motti he is 6/9.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_005
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:9
