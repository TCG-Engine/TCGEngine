# SHD_012 Bo-Katan Kryze — Deployed: Both OnAttack hits fire when another Mandalorian attacked first.
# SOR_162 (Fang Fighter, Mandalorian Space unit) attacks base first,
# then Bo-Katan attacks — total Mandalorian attacks >= 2 → second ability available.

## GIVEN
P1LeaderBase: SHD_012/SOR_026
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6:SOR_095
WithP1SpaceArena: SOR_162:2:0
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED
