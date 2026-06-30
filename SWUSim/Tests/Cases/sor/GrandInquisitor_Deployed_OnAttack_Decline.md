# SOR_011 Grand Inquisitor — Deployed: the On Attack damage-and-ready is optional ("you may").
# Declining the MZMAYCHOOSE leaves the other friendly unit untouched (no damage, still exhausted);
# the attack still deals its base damage.

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
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
P1LEADER:DEPLOYED
