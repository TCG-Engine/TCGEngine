# JTL_177 Stay on Target — Attack with a Vehicle; +2/+0 and granted "deals damage to a base: draw a
# card." SOR_237 (2 power) gets +2 → 4, hits P2's base for 4 and draws a card.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_177
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0
