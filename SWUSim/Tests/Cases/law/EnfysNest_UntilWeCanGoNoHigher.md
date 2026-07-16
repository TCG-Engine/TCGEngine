# Deployed_OncePerRound
#// LAW_014 Enfys Nest (deployed) — "Use this ability only once each round."
#// Two IBH_006 Y-Wings each attack P2's base in space. The FIRST On Attack is reused
#// (1 + 1 + combat 2 = 4); the SECOND attack's On Attack gets NO reuse offer this round
#// (1 + combat 2 = 3). Total P2 base damage = 7, and the second attack auto-completes
#// with no dangling decision.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:7
P1NODECISION

---

# Deployed_ReuseGrantedOnAttack
#// LAW_014 Enfys Nest (deployed) — an UPGRADE-GRANTED On Attack ability counts as the unit's
#// own On Attack ability and is reusable. SOR_214 Smuggling Compartment grants the host
#// "On Attack: Ready a resource." The X-Wing attacks P2's base; the granted On Attack readies
#// one exhausted resource, and Enfys (deployed, free) uses it again → a second resource readies.
#// Starting from 2 exhausted resources, both end ready.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_046:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2

---

# Deployed_ReuseOnAttack
#// LAW_014 Enfys Nest (deployed leader unit) — When you use an "On Attack" ability:
#// you may use that ability again (NO resource cost; once each round).
#// Enfys is deployed in the ground arena. IBH_006 attacks P2's base in space → On Attack
#// deals 1; Enfys lets P1 use it again (free) → 1 more; combat 2 → P2 base = 4.
#// No resources are spent (deployed reuse is free).

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1RESAVAILABLE:2

---

# Undeployed_DeclineReuse
#// LAW_014 Enfys Nest (undeployed) — declining the reuse: nothing is paid and the
#// On Attack ability runs only once. On Attack deals 1 + combat 2 → P2 base = 3.
#// Leader stays ready, both resources are untouched.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1LEADER:READY
P1RESAVAILABLE:2

---

# Undeployed_ReuseOnAttack
#// LAW_014 Enfys Nest (undeployed leader) — When you use an "On Attack" ability:
#// you may pay 2 resources and exhaust this leader; if you do, use that ability again.
#// IBH_006 Rebellion Y-Wing (On Attack: deal 1 to a base) attacks P2's base in space.
#// On Attack deals 1; Enfys reuse deals 1 more; combat (power 2) deals 2 → P2 base = 4.
#// Leader exhausts, the 2 resources are spent.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Undeployed_Unaffordable_NoOffer
#// LAW_014 Enfys Nest (undeployed) — with only 1 ready resource the player can't pay
#// the 2-resource cost, so NO reuse offer is made at all (full no-op on the reaction).
#// On Attack deals 1 + combat 2 → P2 base = 3; leader stays ready, the resource is kept,
#// and there is no dangling decision.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3
P1LEADER:READY
P1RESAVAILABLE:1
P1NODECISION
