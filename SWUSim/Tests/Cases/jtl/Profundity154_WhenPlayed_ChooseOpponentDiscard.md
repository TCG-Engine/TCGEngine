# JTL_154 Profundity — When Played: Choose a player; they discard a card. P1 chooses the Opponent, whose
# 1-card hand auto-discards. The conditional second discard does not fire (P2 ends at 0 cards, not more
# than P1's 0).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_154
WithP1Resources: 13
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P1SPACEARENACOUNT:1
P2HANDCOUNT:0
P2DISCARDCOUNT:1
