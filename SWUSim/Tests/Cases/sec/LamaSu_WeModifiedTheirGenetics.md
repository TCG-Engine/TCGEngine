# Deployed_CompletesAttack_PlayUpgradeFromDiscard
#// SEC_003 Lama Su (deployed) — "When this unit completes an attack (and survives): You may play an
#// upgrade from your discard pile on a friendly non-Vehicle unit. It costs 1 resource less."
#// SEC_003 (3/7) attacks the enemy base (no counter, survives) → onAttackEnd → plays SOR_070 (Vigilance,
#// +1/+1, cost 2 → 1) from discard onto the friendly non-Vehicle SOR_095 (3/3 → 4/4). bbk base covers
#// Vigilance. 3 ready → 2 (proves the −1). No "deal 1" rider on the deploy side.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3;discardCardIds:SOR_070}
P1OnlyActions: true
WithP1GroundArena: SEC_003:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_070
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:1:DAMAGE:0
P1RESAVAILABLE:2
P1DISCARDCOUNT:0

---

# LeaderAction_OnlyVehicle_NoOp
#// SEC_003 Lama Su (leader) — the upgrade must go on a friendly NON-Vehicle unit. With only a friendly
#// Vehicle (SOR_237 X-Wing) in play, there is no valid host: the action is unaffordable → leader stays
#// READY, the upgrade stays in hand, resources unspent.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_070
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1NODECISION

---

# LeaderAction_PlayUpgradeDiscount_Deal1
#// SEC_003 Lama Su (leader) — Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle
#// unit. It costs 1 resource less. If you do, deal 1 damage to that unit.
#// P1 plays SOR_070 (Vigilance upgrade, +1/+1, cost 2 → 1 with the discount; base JTL_019 covers Vigilance)
#// onto the only friendly non-Vehicle unit SOR_095 (3/3 → 4/4), then deals 1 to it (DAMAGE:1).
#// 4 ready → 3 (paid 1, proving the −1: full cost 2 would leave 2).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_070
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_070
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:3
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
