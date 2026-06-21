# SEC_091 Corporate Warmongering (event, cost 4) — Give a friendly unit +3/+3 for this phase; give
#   each other friendly unit +1/+1 for this phase. P1 picks SEC_041 (1/4 → 4/7); SEC_042 (2/2 → 3/3)
#   gets the +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1Hand: SEC_091

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
