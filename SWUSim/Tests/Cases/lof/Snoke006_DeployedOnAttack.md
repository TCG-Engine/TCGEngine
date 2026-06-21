# LOF_006 Supreme Leader Snoke (deployed, Villainy) — On Attack: give an Experience token to the highest-
# power friendly Villainy unit (herself, the only one) → 5/6.

## GIVEN
P1LeaderBase: LOF_006/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
