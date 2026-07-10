## GIVEN
SkipPreGame: true
CommonSetup: ygk/grw/{
  myLeader:JTL_006;
  myBase:SEC_025;
  myBaseDamage:16;
  theirLeader:JTL_012;
  theirBase:JTL_024;
  theirBaseDamage:23
}
WithP1Resources: 5
WithP2Resources: 5
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

WithP2GroundArena: ASH_155:1:5
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>Pass
- P2>Claim
- P2>AnswerDecision:myGroundArena-0
- P2>UseLeaderAbility

## EXPECT
P1BASEDMG:18
P2LEADER:READY