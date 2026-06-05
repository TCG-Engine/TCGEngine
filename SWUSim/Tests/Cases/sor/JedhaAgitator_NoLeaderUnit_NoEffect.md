# SOR_158 Jedha Agitator — the On Attack is gated on "If you control a leader unit." With NO deployed
# leader, the ability does nothing: Jedha's attack deals only its combat damage to the base, no target
# choice is offered, and the enemy unit is untouched.

## GIVEN
P1LeaderBase: SOR_014:1:0:0/SOR_026
P2LeaderBase: SOR_010:1:0:0/SOR_027
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
