# JTL_001 Asajj Ventress — Deploy as Pilot onto SOR_225 (TIE/ln Fighter, Space).
# Deploy threshold = 6. SOR_225 base 2/1; JTL_001 upgradePower=3, upgradeHp=4 → host 5/5.
# deployBox: "Attached unit is a leader unit. It gains Grit and: On Attack..."
# One friendly Vehicle present → DeployLeader offers Unit/Pilot choice.
# Player picks Pilot → auto-attaches to the single Vehicle (no MZCHOOSE).
# After: host has JTL_001 as upgrade, power=5, hp=5, Grit, is a Leader Unit.
# Leader: EpicActionUsed, Deployed. Resources unchanged (deploy is free).

## GIVEN
CommonSetup: gbk/gbk/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirLeader:JTL_001;
  theirBase:SOR_022
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1LEADER:EXHAUSTED
P1RESAVAILABLE:6
