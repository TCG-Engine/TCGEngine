# Deployed_OncePerRound
#// LAW_014 Enfys Nest (deployed) — "Use this ability only once each round."
#// Two IBH_006 Y-Wings each attack P2's base in space. The FIRST On Attack is reused
#// (1 + 1 + combat 2 = 4); the SECOND attack's On Attack gets NO reuse offer this round
#// (1 + combat 2 = 3). Total P2 base damage = 7, and the second attack auto-completes
#// with no dangling decision.
#// COVERAGE: offer=absence asserted via P1NODECISION in Undeployed_Unaffordable_NoOffer +
#//           Undeployed_NoReuseForEnemyOnAttack + Undeployed_NoOfferForWhenDefeatedPartOfCombinedAbility;
#//           the positive offer is a YES/NO, consumed where answered · decline=Undeployed_DeclineReuse +
#//           Undeployed_DeclineDoesNotConsumeOncePerRound · control=N/A (no control-change interaction
#//           on this leader) · boundary=Undeployed_Unaffordable_NoOffer (1 resource, can't pay) vs
#//           Undeployed_ReuseOnAttack (exactly 2); Deployed_OncePerRound +
#//           Undeployed_ExhaustedLeaderNoSecondReuse (first vs second use in a round) ·
#//           reqboundary=every attack -> reuse-answer -> later-attack flow crosses a request boundary
#//           per WHEN step

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

---

# Undeployed_ExhaustedLeaderNoSecondReuse
#// LAW_014 Enfys Nest (undeployed) — the reuse exhausts the leader, so a LATER On Attack the same
#// phase gets NO reuse offer (the leader is already exhausted / no resources left). Two IBH_006 Y-Wings
#// attack P2's base in space. First attack: On Attack 1 + reuse 1 + combat 2 = 4 (leader exhausts, 2
#// resources spent). Second attack: On Attack 1 + combat 2 = 3, no reuse offered. P2 base = 7.

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
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:7
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Undeployed_NoReuseForEnemyOnAttack
#// LAW_014 Enfys Nest (undeployed) — the reuse only applies to a FRIENDLY On Attack. P2 attacks P1's
#// base with IBH_006 Y-Wing (On Attack: deal 1 to a base). Enfys (P1's leader) offers nothing, stays
#// ready, and P1's base just takes On Attack 1 + combat 2 = 3.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2SpaceArena: IBH_006:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P1LEADER:READY
P1NODECISION

---

# Undeployed_DeclineDoesNotConsumeOncePerRound
#// LAW_014 Enfys Nest (undeployed) — DECLINING the reuse offer does not consume the once-per-round
#// use: a LATER friendly On Attack the same round still gets the offer, and accepting it works.
#// Two IBH_006 Y-Wings attack P2's base. First attack: offer declined → 1 + combat 2 = 3.
#// Second attack: offer made again, accepted → 1 + 1 + combat 2 = 4. P2 base = 7; leader ends
#// exhausted with both resources spent.

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
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:7
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Undeployed_NoOfferForWhenDefeatedPartOfCombinedAbility
#// LAW_014 Enfys Nest (undeployed, ready, 2 resources banked) — the reuse hooks "On Attack"
#// abilities ONLY. JTL_090 Executor's combined "When Played/On Attack/When Defeated: create 3 TIE
#// Fighter tokens" resolving as a WHEN DEFEATED must NOT raise a reuse offer, even though the same
#// printed ability is also an On Attack. P2 defeats the seated Executor with SHD_078 Fell the
#// Dragon (defeat a non-leader unit with 5 or more power): P1 gets exactly 3 TIE Fighter tokens,
#// the leader stays ready, and P1 has no pending decision.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:4
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: JTL_090:1:0
WithP2Hand: SHD_078

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1DISCARDCOUNT:1
P1LEADER:READY
P1RESAVAILABLE:2
P1NODECISION

---

# Undeployed_ReusedAethersprite_TripleWhenPlayed
#// LAW_014 Enfys Nest (undeployed) + LOF_197 Qui-Gon Jinn's Aethersprite ("On Attack: The next time
#// you use a 'When Played' ability this phase, you may use that ability again"). Enfys reuses the
#// Aethersprite's On Attack, so the repeat effect is applied TWICE and must STACK. Playing TWI_198
#// Enfys Nest, Champion of Justice ("When Played: you may return an enemy non-leader unit with less
#// power than this unit to its owner's hand") then resolves the return three times in total —
#// all three enemy ground units (power 3 < 5) go back to P2's hand.

## GIVEN
CommonSetup: yrw/bbk/{myLeader:LAW_014}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1SpaceArena: LOF_197:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: TWI_198

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:3
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION
