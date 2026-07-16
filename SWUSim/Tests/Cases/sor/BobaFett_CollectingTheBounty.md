# Deployed_OnAttackEnd_Ready2
#// SOR_015 Boba Fett (deployed, 4/7) — "When this unit completes an attack: If an enemy unit left
#// play this phase, ready up to 2 resources." Boba attacks and defeats P2's 3/1 (so an enemy left
#// play this phase); his OnAttackEnd then readies 2 of P1's exhausted resources.

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
