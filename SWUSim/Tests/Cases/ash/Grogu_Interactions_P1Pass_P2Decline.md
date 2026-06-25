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

WithP2GroundArena: ASH_155:1:5
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>Pass
- P2>Claim
- P2>AnswerDecision:-
- P2>AttackGroundArena:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:16
P1LEADER:READY