# SHD_008 Boba Fett (front) — the reaction is a "may": declining leaves Boba ready and applies no buff.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
