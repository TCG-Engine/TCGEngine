# LOF_223 Force Illusion — Exhaust an enemy unit. A friendly unit gains Sentinel for this phase. The enemy
# SOR_046 is exhausted; Plo Koon gains Sentinel.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
