# DISCLOSE (CR §38) — optional disclose declined → effect does not resolve
# SEC_062 "When Played: You may disclose Vigilance ... If you do, draw a card."
#
# P1 CAN meet the requirement (SEC_059, a Vigilance card, is in hand) but chooses not to
# disclose (confirms the picker with nothing selected → answer "-"). No draw occurs and the
# Vigilance card stays in hand.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_062
WithP1Hand: SEC_059
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_062
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION
