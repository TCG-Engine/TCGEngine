# EpicMill3ReturnTop
#// LAW_026 Shipbreaking Yard (Base, Aggression) — "Epic Action: Discard 3 cards from your deck. You may
#// return a card discarded this way to the top of your deck." P1 mills SOR_046/SOR_095/SOR_128 then
#// returns SOR_046 (myDiscard-0) to the top → deck top = SOR_046, deck count 1, discard 2.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P1BASE:EPICUSED

---

# EpicMillOnly2InDeck
#// LAW_026 Shipbreaking Yard — with only 2 cards in the deck, "discard the top 3" discards all it can
#// (2). P1 mills SOR_046/SOR_095 then returns SOR_046 (myDiscard-0) to the top → deck top = SOR_046,
#// deck count 1, discard 1. Epic consumed.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1BASE:EPICUSED

---

# EpicMill3PassReturn
#// LAW_026 Shipbreaking Yard — the "you may return" is optional. P1 mills the top 3 (SOR_046/SOR_095/
#// SOR_128) then passes the return, so all 3 stay in the discard pile and the 4th card (SOR_237) is now
#// the top of the deck. Deck count 1, discard 3. Epic consumed.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:PASS

## EXPECT
P1DECKTOPCARD:SOR_237
P1DECKCOUNT:1
P1DISCARDCOUNT:3
P1BASE:EPICUSED

---

# EpicEmptyDeckNoOp
#// LAW_026 Shipbreaking Yard — the Epic Action can be used with an empty deck: nothing is discarded, there
#// is no return decision, and the Epic Action is still consumed (once per game).

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1BASE:EPICUSED
P1NODECISION
