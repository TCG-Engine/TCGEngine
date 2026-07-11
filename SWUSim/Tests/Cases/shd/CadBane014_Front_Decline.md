# SHD_014 Cad Bane (front) — declining the "may" leaves Cad Bane ready and deals no damage.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0
