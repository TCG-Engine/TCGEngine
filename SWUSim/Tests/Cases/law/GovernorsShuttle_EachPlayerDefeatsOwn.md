# LAW_099 Governor's Shuttle (2/4) — When Played: each player chooses a unit they control. Defeat those
# units. P1 picks its SEC_080 (keeps the Shuttle); P2 picks its SOR_046.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0
