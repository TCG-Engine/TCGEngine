# LOF_014 Grand Inquisitor (deployed) — On Attack: the defender gets -2/-0 for this attack (applied
# synchronously in ExecuteSWUAttack, mirroring SOR_212). He deploys with a Shield (his Shielded), attacks
# SOR_046 for 3, and the Shield absorbs the reduced counter so he takes 0. (The -2/-0 itself is masked by
# the innate Shield here; it's verified directly by the leader-side LOF_014 and SOR_212 tests.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014;
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
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
