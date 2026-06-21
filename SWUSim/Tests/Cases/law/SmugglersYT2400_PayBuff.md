# LAW_148 Smuggler's YT-2400 (4/5, space, Ambush) — When Played: you may pay 1 resource. If you do,
# this unit gets +1/+1 for this phase. No enemy (Ambush no trigger); pay 1 -> 5/6.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
