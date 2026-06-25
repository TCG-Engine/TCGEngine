# LOF_008 Obi-Wan Kenobi (deployed) — On Attack: may give an Experience token to another unit without one.
# He attacks the base and gives SOR_046 an Experience token → 4/8.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_008;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
