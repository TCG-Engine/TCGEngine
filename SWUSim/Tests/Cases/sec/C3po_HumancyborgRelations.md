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

---

# LeaderAction_NoExhausted_NoOp
#// SEC_015 C-3PO (leader) — Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a
#// unit. Here P1 controls NO exhausted unit (its ground unit is ready), so the "exhaust a unit" effect
#// does nothing — but the ability still pays 1 and exhausts C-3PO. The enemy unit stays ready.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_NoExhausted_NoOp
#// SEC_015 C-3PO (deployed) — On Attack: If you control ANOTHER exhausted unit, you may exhaust a unit.
#// Here the only other friendly unit is ready, so the condition fails and nothing is exhausted when the
#// deployed C-3PO attacks the enemy base.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Deployed_AttackGrantedByAnotherCardStillFiresTheOnAttack
#// SEC_015 C-3PO (deployed) — his On Attack fires on ANY attack he makes, including one handed to him by
#// another card rather than declared as the action. P1 plays SOR_220 Surprise Strike; C-3PO is the only
#// ready unit so he is auto-chosen as the attacker, hits P2's base for 1 + 3 = 4, and his On Attack still
#// resolves (P1 controls the exhausted SOR_095) to exhaust P2's ready SOR_128.
#// Guards the event-granted attack dispatch path, which reaches combat through different code than a
#// normal attack declaration.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_220
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
