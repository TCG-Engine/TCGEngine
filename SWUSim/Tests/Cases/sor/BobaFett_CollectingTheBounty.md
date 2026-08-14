# Deployed_OnAttackEnd_Ready2
#// SOR_015 Boba Fett (deployed, 4/7) — "When this unit completes an attack: If an enemy unit left
#// play this phase, ready up to 2 resources." Boba attacks and defeats P2's 3/1 (so an enemy left
#// play this phase); his OnAttackEnd then readies 2 of P1's exhausted resources.
#// COVERAGE: offer=N/A (front reaction is an always-yes auto-resolve with no target pick; deployed
#//           "ready up to 2" auto-resolves for the full benefit, so no offer ever surfaces) ·
#//           decline=N/A (standing ruling: the "you may exhaust" is an always-yes auto-resolve; the
#//           no-benefit skip is pinned by EnemyDefeated_FullResources_NoReady) ·
#//           control=EnemyTakenByNoGlory_NoReady + FriendlyTakenByNoGlory_ReadyResource (front) and
#//           Deployed_EnemyTakenByNoGlory_NoReadyAtAttackEnd + Deployed_FriendlyTakenByNoGlory_Ready2
#//           (deployed) — both directions of a control-change defeat ·
#//           boundary=EnemyDefeated_FullResources_NoReady vs EnemyDefeated_ReadyResource (0 vs 1
#//           exhausted resource) and Deployed_DiesAttacking_NoReady vs Deployed_OnAttackEnd_Ready2
#//           (the completes-an-attack survival gate) ·
#//           reqboundary=the Deployed_*NoGlory* sections carry the left-play-this-phase flag across
#//           action boundaries into the attack end; FriendlyTakenByNoGlory_ReadyResource resolves the
#//           cross-seat reaction through the reactor-seat drain.

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1RESAVAILABLE:2

---

# EnemyBounced_Waylay_ReadyResource
#// SOR_015 Boba Fett (leader) — "leaves play" is broader than "defeated": a BOUNCE counts too.
#// P1 plays Waylay (SOR_222) to return P2's only unit to hand; that enemy leaving play triggers
#// Boba's always-yes reaction → exhaust the leader, ready a resource. P1 has 3 ready (spent on
#// Waylay) + 1 exhausted; after Waylay all 4 are exhausted, then Boba readies one back to 1 ready.

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_222
WithP1Resources: 3:SOR_128:1,1:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# EnemyDefeatedByEffect_ReadyResource
#// SOR_015 Boba Fett — "When an enemy unit leaves play" fires on a DIRECT-DEFEAT effect too (not
#// just combat/bounce). P1 plays Takedown to defeat P2's 3/1; Boba auto-exhausts to ready a resource.
#// (Confirms the leave-play reactions are collected by SWUDefeatUnit, the single effect-defeat point.)

## GIVEN
CommonSetup: byk/brw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_077
WithP1Resources: 4:SOR_128:1,1:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# EnemyDefeated_FullResources_NoReady
#// SOR_015 Boba Fett (leader) — the always-yes auto-resolve is SKIPPED when there is no benefit.
#// P1's resources are all ready (full), so there is nothing to ready: Boba is NOT exhausted and the
#// enemy defeat triggers no resource change.

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Resources: 1:SOR_128:1

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:READY
P1RESAVAILABLE:1

---

# EnemyDefeated_ReadyResource
#// SOR_015 Boba Fett (leader, undeployed) — "When an enemy unit leaves play: You may exhaust this
#// leader. If you do, ready a resource." Treated as an always-yes auto-resolve: P1's 4/7 defeats
#// P2's 3/1, and because P1 has an exhausted resource to ready, Boba auto-exhausts and readies it
#// (no prompt).

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Resources: 1:SOR_128:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# EnemyLeaderUnitDefeated_ReadyResource
#// SOR_015 Boba Fett (leader, undeployed) — an enemy DEPLOYED LEADER unit leaving play counts:
#// "an enemy unit" includes leader units. P1 plays Rival's Fall (SHD_079, cost 6) defeating P2's
#// deployed Luke (SOR_005, sole unit → auto-target); Luke returns to the base zone (unit left play)
#// and Boba auto-exhausts to ready a resource. 6 ready + 1 exhausted → pay 6 → ready 1 back.

## GIVEN
CommonSetup: byk/brw/{
  myLeader:SOR_015;
  theirLeader:SOR_005:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1Resources: 6:SOR_128:1,1:SOR_128:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# FriendlyDefeated_NoReady
#// SOR_015 Boba Fett (leader, undeployed) — a FRIENDLY unit leaving play does NOT trigger the
#// reaction. P1 plays Takedown (SOR_077) on its own 3/1 (sole ≤5-HP unit → auto-target): the unit
#// dies but Boba stays ready and no resource is readied (the pre-exhausted resource stays exhausted).

## GIVEN
CommonSetup: byk/brw/{myLeader:SOR_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_077
WithP1GroundArena: SOR_128:1:0
WithP1Resources: 4:SOR_128:1,1:SOR_128:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1LEADER:READY
P1RESAVAILABLE:0

---

# EnemyTakenByNoGlory_NoReady
#// SOR_015 Boba Fett (leader, undeployed) — control change flips the trigger OFF: P1 plays
#// No Glory, Only Results (JTL_043: take control of a non-leader unit, then defeat it) on P2's
#// Wampa. At the defeat the Wampa is under P1's control, so from P1's perspective a FRIENDLY unit
#// left play → Boba does not trigger. Wampa still goes to its owner's (P2) discard.

## GIVEN
CommonSetup: byk/brw/{myLeader:SOR_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SOR_164:1:0
WithP1Resources: 5:SOR_128:1,1:SOR_128:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1LEADER:READY
P1RESAVAILABLE:0

---

# FriendlyTakenByNoGlory_ReadyResource
#// SOR_015 Boba Fett (leader, undeployed) — control change flips the trigger ON: P2 plays
#// No Glory, Only Results (JTL_043) on P1's 3/1 (sole non-leader unit → auto-target). At the
#// defeat the unit is under P2's control, so from P1's perspective an ENEMY unit left play →
#// Boba auto-exhausts and readies P1's one exhausted resource. The unit still goes to its
#// owner's (P1) discard. The reaction queues on the non-active reactor's seat, so P1>Drain
#// surfaces it before the end-state read.

## GIVEN
CommonSetup: byk/bbk/{myLeader:SOR_015}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: SOR_128:1:0
WithP1Resources: 1:SOR_128:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# Deployed_DiesAttacking_NoReady
#// SOR_015 Boba Fett (deployed, 4/7 with 4 damage) — "completes an attack" requires Boba to
#// SURVIVE it. He attacks P2's 3/3: the 3/3 dies (an enemy left play this phase) but Boba takes
#// 3, hits 7 total damage and is defeated too — no attack-completed trigger, no resources readied.
#// His leader returns to the base zone (and comes back exhausted, so the undeployed-side reaction
#// on the enemy defeat cannot be paid either).

## GIVEN
CommonSetup: ryk/brw/{myLeader:SOR_015:1:1:0:4}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 2:SOR_128:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1RESAVAILABLE:0

---

# Deployed_EnemyTakenByNoGlory_NoReadyAtAttackEnd
#// SOR_015 Boba Fett (deployed) — "an enemy unit left play this phase" is judged by control at the
#// moment it left: P1 plays No Glory, Only Results on P2's 3/3 (control passes to P1 before the
#// defeat, so a FRIENDLY unit left play), then Boba attacks the base and completes the attack —
#// no ready. 5 ready pay for the event + 2 pre-exhausted stay exhausted → 0 available at the end.

## GIVEN
CommonSetup: byk/brw/{myLeader:SOR_015:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 5:SOR_128:1,2:SOR_128:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# Deployed_FriendlyTakenByNoGlory_Ready2
#// SOR_015 Boba Fett (deployed) — the mirror case: P2 plays No Glory, Only Results on P1's 3/1
#// (control passes to P2 before the defeat, so from P1's side an ENEMY unit left play this phase).
#// Boba then attacks the base and completes the attack → readies up to 2 of P1's 3 exhausted
#// resources (auto-resolves for the full 2).

## GIVEN
CommonSetup: ryk/bbk/{myLeader:SOR_015:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: SOR_128:1:0
WithP1Resources: 3:SOR_128:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1RESAVAILABLE:2
