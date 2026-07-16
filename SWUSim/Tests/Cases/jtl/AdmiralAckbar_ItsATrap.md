# Deploy_OnAttack_ExhaustUnit_XWing
#// JTL_016 Admiral Ackbar (deployed leader unit) — On Attack: You may exhaust a unit. If you do, its
#// controller creates an X-Wing token. P1 deploys Ackbar (control 6+ resources), attacks P2's base, and
#// on attack exhausts the enemy SOR_095, so P2 creates an X-Wing.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_T02
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# LeaderAction_ExhaustEnemy_OpponentGetsXWing
#// JTL_016 Admiral Ackbar (leader) — Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do,
#// its controller creates an X-Wing token. P1 exhausts the enemy SOR_095, so P2 (its controller) creates
#// an X-Wing (JTL_T02) in P2's space arena.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_T02
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoResource_NoOp
#// JTL_016 Admiral Ackbar (leader) — the action costs 1 resource. With 0 ready resources it is a full
#// no-op: Ackbar stays READY, the enemy unit is not exhausted, no X-Wing is created, and no decision is
#// pending.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2GROUNDARENAUNIT:0:READY
P2SPACEARENACOUNT:0
