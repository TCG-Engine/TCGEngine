# ChooseOne_UpTo
#// TWI_088 Reprocess — "up to 4": choose only ONE of the two discard units. That one goes to the deck
#// bottom (deck 1 → 2) and exactly 1 Battle Droid is created ("that many"). The unchosen unit stays in
#// discard alongside the event (discard count 2).

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Discard: JTL_069
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1DISCARDCOUNT:2
P1DECKCOUNT:2

---

# ChooseTwo_BottomAndCreate2
#// TWI_088 Reprocess (Event, cost 3, Command/Villainy) — "Choose up to 4 units in your discard pile.
#// Put them on the bottom of your deck in a random order and create that many Battle Droid tokens."
#// Discard seeded with 2 units (SEC_080, JTL_069); choose both via MZMULTICHOOSE. Both go to the deck
#// bottom (deck 2 → 4), leaving only the Reprocess event in discard, and 2 Battle Droids are created.

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Discard: JTL_069
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1DISCARDCOUNT:1
P1DECKCOUNT:4

---

# EmptyDiscard_Fizzle
#// TWI_088 Reprocess — no units in the discard pile → the effect fizzles cleanly: no MZMULTICHOOSE,
#// no tokens created ("that many" = 0). Only the Reprocess event sits in discard afterward.

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088}
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:1
P1NODECISION
