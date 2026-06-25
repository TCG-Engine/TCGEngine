# SOR_208 Outer Rim Headhunter — the exhaust is conditional on controlling a LEADER unit.
# Here the leader is NOT deployed, so on attack nothing is offered: no decision is pending
# and the enemy unit stays ready. Absence guard for the leader-unit condition.

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0     # attacker
WithP2GroundArena: SOR_095:1:0    # enemy unit — must stay ready

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION
