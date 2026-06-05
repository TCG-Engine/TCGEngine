# JTL_158 Crackshot V-Wing — When Played: If you control no other Fighter units, deal 1 damage to this
# unit. With another Fighter (SOR_237) already in play, the V-Wing takes no damage.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: JTL_158
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:DAMAGE:0
