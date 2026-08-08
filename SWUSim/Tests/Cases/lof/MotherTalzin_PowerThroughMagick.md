# ForceMinus1
#// LOF_002 Mother Talzin — Action [Exhaust, use the Force]: Give a unit -1/-1 for this phase. SOR_046 (3/7)
#// becomes 2/6 and P1 loses the Force token.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P1NOFORCE

---

# DeployedOnAttack
#// LOF_002 Mother Talzin (deployed) — On Attack: may give a unit -1/-1. Deployed, she attacks the base; her
#// On Attack drops SOR_046 to 2/6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
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
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2

---

# Undeployed_NoForce_NotUsable
#// LOF_002 Mother Talzin (undeployed) — the Action ability costs the Force, so with NO Force token it can't be
#// used: invoking it is a no-op — the leader stays READY and no -1/-1 is applied (SOR_046 stays 3/7). (Intended: #// "should not be selectable without the Force".)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
