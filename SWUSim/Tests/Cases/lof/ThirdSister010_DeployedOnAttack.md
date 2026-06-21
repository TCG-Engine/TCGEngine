# LOF_010 Third Sister (deployed) — On Attack: the next unit you play this phase gains Hidden. She attacks
# the base, then P1 plays Plo Koon, who enters with Hidden.

## GIVEN
P1LeaderBase: LOF_010/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: LOF_050

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_050
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
