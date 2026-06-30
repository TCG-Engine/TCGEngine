# JTL_014 Admiral Trench (leader) — Action [Exhaust]: Discard a card that costs 3 or more from your
# hand. If you do, draw a card. P1's only hand card JTL_069 (cost 5) is discarded and P1 draws SOR_128
# from the deck.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_069
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
