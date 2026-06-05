# JTL_059 Corporate Defense Shuttle — This unit can't attack. Attempting to attack the base is a no-op:
# no damage is dealt and the shuttle stays ready.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_059:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:JTL_059
P1SPACEARENAUNIT:0:READY
