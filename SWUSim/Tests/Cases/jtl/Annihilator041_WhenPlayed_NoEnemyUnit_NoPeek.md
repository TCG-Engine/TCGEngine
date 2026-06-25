# JTL_041 Annihilator — with NO enemy unit in play there is nothing to defeat, so the ability fizzles
# before offering anything: no "may defeat" prompt, no name-hunt, and NO peek at P2's hand or deck.
# P2 keeps its hand copy and deck copy of SOR_225, nothing is discarded, and no decision is pending.
# Proves the peek never happens when no enemy unit is defeated (even though P2 holds matching cards).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2Deck: SOR_225
WithP2Hand: SOR_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_041
P2DECKCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION
