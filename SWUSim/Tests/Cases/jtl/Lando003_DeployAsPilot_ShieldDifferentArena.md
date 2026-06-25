# JTL_003 Lando Calrissian (leader) — "When deployed as an upgrade: You may give a Shield token to a
# unit in a different arena." Lando deploys as a Pilot onto a space Vehicle (SOR_237), then shields a
# unit in the GROUND arena (the only other-arena unit, SOR_095).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
