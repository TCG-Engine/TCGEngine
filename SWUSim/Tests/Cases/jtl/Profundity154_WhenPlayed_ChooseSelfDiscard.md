# JTL_154 Profundity — When Played: Choose a player; they discard a card. P1 chooses itself (You) and
# discards one card from hand. The follow-up second discard does not fire (P1's hand is not larger than
# its own). (The conditional cross-player second discard needs interactive opponent input — deferred.)

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_154
WithP1Hand: SOR_095
WithP1Hand: SOR_128
WithP1Resources: 13

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1
