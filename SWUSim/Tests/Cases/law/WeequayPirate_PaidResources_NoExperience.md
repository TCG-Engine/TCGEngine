# LAW_231 Weequay Pirate (Ground Unit 3/2, cost 2, Cunning/Underworld) —
# "When Played: If no resources were paid to play this unit, give an Experience token to it."
# Guard: P1 plays LAW_231 from hand paying its full cost of 2 resources → SWU_PAID_2 is stamped.
# SWUUnitResourcesPaid returns 2 ≠ 0, so NO Experience token is granted.
# Weequay Pirate enters as a bare 3/2 unit with no subcards.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:LAW_231}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0
