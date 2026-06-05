# SOR_015 Boba Fett (deployed, 4/7) — "When this unit completes an attack: If an enemy unit left
# play this phase, ready up to 2 resources." Boba attacks and defeats P2's 3/1 (so an enemy left
# play this phase); his OnAttackEnd then readies 2 of P1's exhausted resources.

## GIVEN
P1LeaderBase: SOR_015/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1RESAVAILABLE:2
