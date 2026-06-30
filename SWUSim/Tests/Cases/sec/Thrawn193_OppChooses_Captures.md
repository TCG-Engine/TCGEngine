# SEC_193 Grand Admiral Thrawn (Ground, 8/7, Cunning/Villainy, cost 7) — When Played: an opponent may
#   choose a non-leader unit they control; if they do, Thrawn captures it. P2 picks SOR_046 → captured.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_193

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
