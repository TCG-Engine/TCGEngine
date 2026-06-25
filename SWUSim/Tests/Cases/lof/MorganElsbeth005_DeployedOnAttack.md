# LOF_005 Morgan Elsbeth (deployed) — On Attack: the next unit you play this phase costs 1 less if it shares
# a keyword with a friendly unit. She attacks the base (arming the discount); P1 then plays LOF_132 (Raid),
# which shares Raid with the friendly LOF_131 — so it costs 3+2−1 = 4 instead of 5.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6
WithP1SpaceArena: LOF_131:1:0
WithP1Hand: LOF_132

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_132
P1RESAVAILABLE:2
