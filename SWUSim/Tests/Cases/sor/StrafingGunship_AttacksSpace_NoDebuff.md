# SOR_212 Strafing Gunship — the -2/-0 applies only while attacking a GROUND unit. Attacking a SPACE
# unit normally, the defender (SOR_237 2/3) deals its full 2 counter-damage, so the Gunship takes 2.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
