# SOR_011 Grand Inquisitor — Deployed: On Attack you MAY deal 1 damage to another friendly
# unit with 3 or less power and ready it. GI (idx 1) attacks the base; the only other friendly
# (a 3/3 at idx 0, exhausted) is chosen → takes 1 damage and is readied. Base takes GI's power 3.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:3
P1LEADER:DEPLOYED
