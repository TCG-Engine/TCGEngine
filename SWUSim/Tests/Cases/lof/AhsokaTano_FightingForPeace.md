# DeployedOnAttack
#// LOF_003 Ahsoka Tano (deployed) — On Attack: may give a friendly unit Sentinel. She attacks the base and
#// grants herself Sentinel.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:LOF_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ForceSentinel
#// LOF_003 Ahsoka Tano — Action [Exhaust, use the Force]: Give a friendly unit Sentinel for this phase. Plo
#// Koon gains Sentinel and P1 loses the Force token.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:LOF_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NOFORCE
