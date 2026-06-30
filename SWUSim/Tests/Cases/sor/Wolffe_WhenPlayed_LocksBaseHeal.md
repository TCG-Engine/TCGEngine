# SOR_160 Wolffe (Aggression unit, cost 2, 3/2, Fringe/Clone) — "Saboteur. When Played/On Attack:
# Bases can't be healed for this phase." P1's base is at 3 damage. Playing Wolffe locks base healing,
# so when the Restore 1 unit (SOR_044) attacks, its Restore heal is blocked and the base stays at 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP1Hand: SOR_160

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:2
