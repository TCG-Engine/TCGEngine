# DeclineDisclose_NoDraw
#// DISCLOSE (CR §38) — optional disclose declined → effect does not resolve
#// SEC_062 "When Played: You may disclose Vigilance ... If you do, draw a card."
#//
#// P1 CAN meet the requirement (SEC_059, a Vigilance card, is in hand) but chooses not to
#// disclose (confirms the picker with nothing selected → answer "-"). No draw occurs and the
#// Vigilance card stays in hand.

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

---

# NoVigilanceInHand_CannotDisclose
#// DISCLOSE (CR §38.3) — cannot meet the requirement → no reveal, effect does not resolve
#// SEC_062 "When Played: You may disclose Vigilance ... If you do, draw a card."
#//
#// P1 plays SEC_062 with a hand that has NO Vigilance card left to disclose (the only other
#// card is SOR_095, a Command/Heroism card). Since the disclose requirement (1 Vigilance)
#// cannot be met, the optional disclose is not offered at all → no draw, no decision pending.

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

---

# WhenPlayed_DiscloseVigilance_Draws
#// DISCLOSE (CR §38) — SEC_062 Bardottan Ornithopter (Unit, cost 4, Vigilance)
#//   "When Played: You may disclose Vigilance (reveal a card from your hand with this aspect
#//    icon). If you do, draw a card."
#// Proves the basic positive disclose path: reveal a hand card whose icons cover the
#// requirement (1 Vigilance) → the "if you do" effect resolves (draw 1). Disclosed cards are
#// only revealed, never discarded — they stay in hand.
#//
#// P1 hand: SEC_062 (to play) + SEC_059 (a Vigilance card used as disclose fodder). bk leader
#// covers Vigilance → SEC_062 costs 4. Deck has 2 cards to draw from.
#// Flow: play SEC_062 → disclose MZMULTICHOOSE over hand → select SEC_059 (covers Vigilance)
#// → draw 1. Hand ends at 2 (SEC_059 stays + 1 drawn); deck 2 → 1.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_062
WithP1Hand: SEC_059
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_062
P1HANDCOUNT:2
P1DECKCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
