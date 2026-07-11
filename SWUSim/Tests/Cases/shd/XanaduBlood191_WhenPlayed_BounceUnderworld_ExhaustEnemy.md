# SHD_191 Xanadu Blood (6-cost space) — Raid 2 + "When Played/On Attack: You may return another friendly
# non-leader Underworld unit to its owner's hand. If you do, exhaust an enemy unit or resource." P1 returns
# the friendly LAW_124 (Underworld) to hand and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_191
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
