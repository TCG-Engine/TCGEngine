# Deployed_OnAttack_ExhaustUnit
#// SEC_015 C-3PO (deployed) — On Attack: If you control another exhausted unit, you may exhaust a unit.
#// Deployed SEC_015 (1/6) attacks the enemy base while controlling another exhausted unit (SOR_095) → may
#// exhaust a unit → exhausts the ready enemy SOR_128.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# LeaderAction_ExhaustUnit
#// SEC_015 C-3PO (leader) — Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit.
#// P1 controls an exhausted SOR_095 (satisfies the condition) → exhausts the ready enemy SOR_128.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
