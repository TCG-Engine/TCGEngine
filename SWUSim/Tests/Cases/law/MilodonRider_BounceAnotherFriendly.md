# LAW_240 Milodon Rider (Cunning, cost 6, Ambush) — When Played: you may return another friendly
# non-leader unit to its owner's hand. No enemy (Ambush no trigger); return SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_240
P1HANDCOUNT:1
