# JTL_152 Tactical Heavy Bomber — On Attack: deal indirect = power to the defending player; if a base is
# damaged this way, draw. P1 attacks P2's base with JTL_152 (power 3). P2 controls no units, so the 3
# indirect auto-resolves onto P2's base → a base is damaged this way → P1 draws a card.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_152:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:1
