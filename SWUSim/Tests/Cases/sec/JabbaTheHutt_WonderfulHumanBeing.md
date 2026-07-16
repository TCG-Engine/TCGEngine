# Deployed_FriendlyDamagedSurvives_DealsBack
#// SEC_002 Jabba the Hutt (deployed) — "When another friendly unit is dealt damage and survives: You may
#// have that unit deal that much damage to an enemy unit. Once each round."
#// P1's SEC_080 (3/3) attacks the enemy SOR_063 (2/4 Sentinel): deals 3 (SOR_063 survives at 4 HP),
#// takes 2 counter-damage and survives. SEC_002 (deployed) reacts → SEC_080 deals that much (2) to an
#// enemy unit. Only enemy = SOR_063 → 3 + 2 = 5 damage on 4 HP → defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# LeaderAction_3PlusDamageDeals2
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals damage
#// to an enemy unit; if the friendly unit has 3 or more damage on it, it deals 2 instead of 1.
#// Friendly LAW_124 (4/7) carries 3 damage → deals 2 to the only enemy (SOR_095, 3/3 survives at 2).
#// Proves the 3+-damage → 2 branch (vs the 1 in the sibling test).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:3
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_DamagedUnitDeals1
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals 1
#// damage to an enemy unit. (If it has 3+ damage it deals 2 instead — see the other test.)
#// Friendly SEC_080 has 1 damage → deals 1 to the only enemy (SOR_095). Both picks auto-resolve.
#// Costs 1 resource (2 ready → 1), leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEnemy_NoOp
#// SEC_002 Jabba the Hutt (leader) — the action needs a friendly damaged unit AND an enemy unit. With a
#// friendly damaged unit but NO enemy unit, the action is unaffordable: leader stays READY, resources
#// unspent, no decision pending (the player keeps their action).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:2
P1NODECISION
