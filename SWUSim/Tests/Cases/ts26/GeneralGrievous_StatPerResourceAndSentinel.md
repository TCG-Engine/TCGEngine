# TS26_050 General Grievous (Unit 0/0, cost 5) — +1/+1 per resource you control; while undamaged he
# gains Sentinel. With 3 resources he is 3/3; undamaged → Sentinel; the damaged copy loses Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1Resources: 3
WithP1GroundArena: [TS26_050:1:0 TS26_050:1:2]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
