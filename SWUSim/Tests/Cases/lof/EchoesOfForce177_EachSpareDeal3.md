# LOF_177 — Each player chooses a unit they control; deal 3 damage to each unit not chosen. P1 has one unit
# (auto-spared); P2 spares SOR_046, so only SOR_059 takes 3.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:3
