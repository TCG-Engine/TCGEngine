# SHD_012 Bo-Katan Kryze — Deployed: OnAttack YES first hit only (no other Mandalorian attacked).

## GIVEN
P1LeaderBase: SHD_012/SOR_026
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6:SOR_095
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EPICUSED
