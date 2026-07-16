# Deploy_OnAttack_Decline
#// JTL_004 Rose Tico (deployed leader unit) — the On Attack heal is optional ("You may"). P1 deploys
#// Rose, attacks P2's base, and DECLINES the heal (AnswerDecision:-): the X-Wing keeps its 2 damage.
#// Proves the may-decline path.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1LEADER:DEPLOYED

---

# Deploy_OnAttack_HealsVehicle
#// JTL_004 Rose Tico (deployed leader unit) — On Attack: You may heal 2 damage from a Vehicle unit
#// (any Vehicle, no "attacked this phase" restriction). P1 deploys Rose (free epic, 5-resource
#// threshold met), attacks P2's base (power 4), and on attack heals 2 from the damaged X-Wing (2 → 0).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:4
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# LeaderAction_HealsVehicleThatAttacked
#// JTL_004 Rose Tico (leader) — Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this
#// phase. P1's damaged X-Wing (SOR_237, 2 damage) attacks P2's base this phase (dealing 2 and marking
#// itself as having attacked), then Rose's leader action heals 2 from it (the only Vehicle that attacked).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_VehicleDidNotAttack_Fizzle
#// JTL_004 Rose Tico (leader) — the heal only applies to a Vehicle that ATTACKED this phase. Here the
#// damaged X-Wing never attacked, so there is no eligible target and the action fizzles (leader still
#// exhausts, the X-Wing keeps its 2 damage). Proves the "that attacked this phase" restriction.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P1NODECISION
