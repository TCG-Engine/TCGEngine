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
