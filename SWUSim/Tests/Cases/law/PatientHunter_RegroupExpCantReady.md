# LAW_073 Patient Hunter (3/3) — When the regroup phase starts: you may give an Experience token to a
# non-leader unit; if you do, that unit can't ready during this regroup phase. Give it to the exhausted
# SEC_080 -> it gains Experience AND stays exhausted through the next ready step.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_073:1:0
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>AnswerDecision:myGroundArena-1
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:EXHAUSTED
