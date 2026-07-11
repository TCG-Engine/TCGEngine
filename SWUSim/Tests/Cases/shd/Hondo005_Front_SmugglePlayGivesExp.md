# SHD_005 Hondo Ohnaka (front, undeployed) — "When you play a card using Smuggle: You may exhaust this
# leader. If you do, give an Experience token to a unit." P1 plays SHD_065 from resources via Smuggle,
# accepts (exhausting Hondo), and gives an Experience token (+1/+1) to its SOR_046 (3/7 → 4 power).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4
