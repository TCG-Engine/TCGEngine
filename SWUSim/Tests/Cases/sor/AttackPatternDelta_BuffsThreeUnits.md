# SOR_106 Attack Pattern Delta — Event, cost 3, double Command (Command/Command).
# "Give a friendly unit +3/+3. Give another friendly unit +2/+2. Give a third friendly unit +1/+1."
# Three distinct friendly units (3x SOR_088, 9/9). Player assigns the buffs:
#   idx0 → +3/+3 = 12/12, idx1 → +2/+2 = 11/11, idx2 (last remaining) → +1/+1 = 10/10.
# Note: ggw/ggw gives Command from BOTH base and leader so the double-Command cost is unpenalized.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:12
P1GROUNDARENAUNIT:1:POWER:11
P1GROUNDARENAUNIT:1:HP:11
P1GROUNDARENAUNIT:2:POWER:10
P1GROUNDARENAUNIT:2:HP:10
