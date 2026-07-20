# OnAttack_Fighter_Deals3
#// JTL_012 deployed as a PILOT on a Fighter host (SOR_237) — the host gains "On Attack: You may deal
#// 3 damage to a unit." Host attacks the base; deals 3 to the enemy JTL_069 (4/7 -> 3 damage).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3

---

# OnAttack_NonFighter_NoGrant
#// JTL_012's grant is gated "If it's a Fighter". On a non-Fighter host (JTL_069, Capital Ship) the
#// On Attack does NOT fire — no decision, the enemy TIE is undamaged.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# AttackedFighter_DealsUnit
#// JTL_012 Luke Skywalker (leader) — Action [Exhaust]: If you attacked with a Fighter unit this phase,
#// deal 1 damage to a unit. P1's X-Wing (SOR_237, a Fighter) attacks P2's base, then Luke's action deals
#// 1 to the enemy SOR_095.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# NoFighterAttack_NoOp
#// JTL_012 Luke Skywalker (leader) — the damage only happens if you attacked with a Fighter this phase.
#// Here P1 never attacked, so the action does nothing (leader still exhausts), nothing is damaged, and no
#// decision is pending. Gate test.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# DeployedPilot_HostDefeated_LukeReturnsToBase
#// JTL_012 Luke deployed as a Pilot on SOR_237 (Fighter host). P2's Rivals Fall (SHD_079: defeat a unit)
#// defeats the host. The host goes to P1's discard; Luke — a leader pilot — is NOT defeated with it but
#// returns to the leader zone, undeployed and exhausted (a state-based consequence, not an enemy defeat).

## GIVEN
CommonSetup: yrk/grw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDUNIT:0:CARDID:SOR_237
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED

---

# DeployedPilot_ImmuneToEnemyUpgradeDefeat
#// JTL_012 Luke deployed as a Pilot "cannot be defeated as an upgrade by enemy card abilities." P2 plays
#// Confiscate (SHD_262: defeat an upgrade) at Luke — the defeat is prevented, so Luke stays attached to
#// the host and the host is unharmed.

## GIVEN
CommonSetup: yrk/grw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SHD_262

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_012
P1LEADER:DEPLOYED

---

# DeployedPilot_FriendlyAbilityCanDefeatUpgrade
#// JTL_012 Luke's immunity is to ENEMY card abilities only — a FRIENDLY ability can still defeat him as an
#// upgrade. P1 plays its own Power Failure (SOR_170: defeat an upgrade) on the host, choosing Luke. Luke
#// returns to the leader zone (undeployed); the host keeps no upgrade.

## GIVEN
CommonSetup: yrk/grw/{myLeader:JTL_012;myLeaderDeployedPilot:true;myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: SOR_170

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:NOTDEPLOYED

---

# DeployedPilot_BamboozleReturnsLukeToBase
#// JTL_012 Luke deployed as a Pilot, hit by Bamboozle (SOR_199: exhaust a unit and return each upgrade on
#// it to its owner's hand). A leader pilot can't be returned to hand, so Luke returns to the leader zone
#// (undeployed, exhausted) instead; the host is exhausted and keeps no upgrade.

## GIVEN
CommonSetup: yrk/grw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SOR_199

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:EXHAUSTED
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED

---

# DeployedPilot_HostIsLeaderUnit_VanquishCannotTarget
#// JTL_012 Luke deployed as a Pilot makes the host a LEADER UNIT, so Vanquish (TWI_077: "defeat a
#// NON-leader unit") cannot target it. P1 has the piloted host (SOR_237+Luke, a leader unit) plus a plain
#// SOR_095; P2 has its own SOR_178. P2 plays Vanquish — its target choice offers EXACTLY the two non-leader
#// units (P2's own SOR_095 = myGroundArena-0 and SOR_178 = mySpaceArena-0, from P2's frame) and NOT the
#// piloted host (theirSpaceArena-0). Uses the P1SELECTABLE exact-target-set assertion (decision left
#// pending). P1 has ONLY the space host so the leader pilot attaches to it (the builder pilots the first
#// friendly GROUND unit if one exists — keep P1's ground empty).

## GIVEN
CommonSetup: yrk/grw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_178:1:0
WithP2Hand: TWI_077

## WHEN
- P2>PlayHand:0

## EXPECT
P2HASDECISION
P2SELECTABLENOT:theirSpaceArena-0
P2SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1NODECISION
