# SHD_012 Bo-Katan Kryze — Deployed: OnAttack declined → no damage beyond combat.
# No other Mandalorian attacked, so only the first "deal 1" question fires.

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
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EPICUSED
