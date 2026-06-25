# JTL_191 Invincible — the bounce filter is "costs 3 or less". P2's only unit is the cost-4 SOR_046
# Consular Security Force, which is NOT eligible, so deploying the leader offers no decision and the
# unit is untouched. (Proves the ≤3 cutoff, distinguishing it from the ≤4 wording on JTL_223 Razor Crest.)

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P1NODECISION
