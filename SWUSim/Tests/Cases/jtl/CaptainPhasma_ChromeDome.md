# Deploy_OnAttack_DealsUnitAndBase
#// JTL_010 Captain Phasma (deployed leader unit) — On Attack: If you played another First Order card
#// this phase, you may deal 1 damage to a unit. If you do, deal 1 damage to a base. P1 deploys Phasma,
#// plays JTL_081 (First Order), then attacks P2's base: on attack it deals 1 to SOR_095 (→1 damage) and
#// 1 to the enemy base; combat then adds Phasma's power 4 → P2 base 5 total.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_081
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:5
P1LEADER:DEPLOYED

---

# LeaderAction_NoFO_NoDamage
#// JTL_010 Captain Phasma (leader) — without having played a First Order card this phase, the action
#// does nothing (the leader still exhausts). No base takes damage and no decision is pending. Gate test.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_PlayedFO_DealsBase
#// JTL_010 Captain Phasma (leader) — Action [Exhaust]: If you played a First Order card this phase, deal
#// 1 damage to a base. P1 plays JTL_081 (First Order, cost 1 — Command base + Phasma's Villainy cover it),
#// then Phasma's action deals 1 to the enemy base.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_081
WithP1Resources: 1

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1LEADER:EXHAUSTED
