# JTL_006 Darth Vader (leader) — "When deployed as an upgrade: Create 2 TIE Fighter tokens." Vader
# deploys as a Pilot onto the lone friendly Vehicle (SOR_225 TIE/ln Fighter), then makes 2 TIE tokens.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1SPACEARENAUNIT:2:CARDID:JTL_T01
