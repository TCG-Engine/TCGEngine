# LAW_023 Great Pit of Carkoon (Base, Command) — "Epic Action [discard a unit from your hand]: Search
# your deck for a card named The Sarlacc of Carkoon, reveal it, and draw it." P1 discards SEC_080 (cost)
# and draws LAW_163 (The Sarlacc of Carkoon) from the deck.

## GIVEN
P1LeaderBase: SOR_005/LAW_023
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Deck: LAW_163

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:LAW_163

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:1
