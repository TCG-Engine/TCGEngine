# JTL_014 Admiral Trench (leader) — the discard requires a card costing 3 or more. With only a cost-1
# card in hand (SOR_225), there is no eligible card to discard, so the action fizzles: nothing is
# discarded, nothing is drawn, and no decision is pending. The leader still exhausts.

## GIVEN
P1LeaderBase: JTL_014/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
