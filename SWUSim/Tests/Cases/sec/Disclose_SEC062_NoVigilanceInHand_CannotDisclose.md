# DISCLOSE (CR §38.3) — cannot meet the requirement → no reveal, effect does not resolve
# SEC_062 "When Played: You may disclose Vigilance ... If you do, draw a card."
#
# P1 plays SEC_062 with a hand that has NO Vigilance card left to disclose (the only other
# card is SOR_095, a Command/Heroism card). Since the disclose requirement (1 Vigilance)
# cannot be met, the optional disclose is not offered at all → no draw, no decision pending.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_062
WithP1Hand: SOR_095
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_062
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION
