# LOF_004 Kanan Jarrus — Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit.
# LOF_044 (a Creature) gets a Shield; the resource is spent.

## GIVEN
P1LeaderBase: LOF_004/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: LOF_044:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
