# LOF_001 Kylo Ren — Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this way,
# draw a card. P1 discards SOR_053 (an upgrade) and draws SOR_059; the leader exhausts.

## GIVEN
P1LeaderBase: LOF_001/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_053
WithP1Deck: SOR_059

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED
