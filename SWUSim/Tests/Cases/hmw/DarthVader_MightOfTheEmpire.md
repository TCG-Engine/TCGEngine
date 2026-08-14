# Front_Cost3Unit_GainsRaid1
#// COVERAGE: offer=N/A (both sides are static auras — no choose pool is ever built) ·
#//           decline=N/A (nothing optional; no cost, no "you may") ·
#//           boundary=Front_Cost3Unit_GainsRaid1 (exactly 3 → granted) +
#//           Front_Cost2Unit_NoRaid (exactly 2 → not granted) ·
#//           control=ControlChange_StolenUnitGainsAuraFromNewController +
#//           ControlChange_UnitUnderOpponentLosesAura (the aura follows the CONTROLLER) ·
#//           reqboundary=N/A (no state is written before a decision and read behind it) ·
#//           persistence=DeployedVaderDefeated_FrontAuraResumes (deployed → defeated → undeployed) ·
#//           arena=Front_SpaceUnitAlsoGainsRaid1 (the aura names no arena)
#// HMW_007 Darth Vader, Might of the Empire (Leader, Command/Villainy, 5/5, cost 6).
#// FRONT: "Friendly units that cost 3 or more gain Raid 1."
#// TWI_230 Super Battle Droid (4/3, cost 3) attacks P2's base: Raid 1 makes it 4+1 = 5.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_230:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# Front_Cost2Unit_NoRaid
#// HMW_007 — the boundary partner: "cost 3 or more" excludes a cost-2 unit. SEC_080 Imperial Dark
#// Trooper (3/3, cost 2) attacks the base for its printed 3 — no Raid bonus, and no Raid keyword.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Front_TokenUnit_NoRaid
#// HMW_007 — value-class variant: a TOKEN unit costs 0, so it never reaches the "3 or more" gate.
#// TWI_T01 Battle Droid (1/1, cost 0) attacks the base for 1.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Front_StacksWithPrintedRaid
#// HMW_007 — quantity discrimination: the grant is ADDITIVE on top of a printed Raid, not a floor.
#// SOR_248 Volunteer Soldier (2/3, cost 3) already has printed Raid 1; under Vader it attacks for
#// 2 + 1 (printed) + 1 (Vader) = 4. A "set to Raid 1" implementation would deal only 3.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_248:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# Front_EnemyUnitExcluded_NoRaid
#// HMW_007 — scope exclusion: "FRIENDLY units". P1 holds Vader; P2's own cost-3 Super Battle Droid
#// gets nothing and attacks P1's base for its printed 4.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: TWI_230:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Deployed_VaderHimself_Raid1_NotTwo
#// HMW_007 DEPLOYED: "Raid 1 / OTHER friendly units that cost 3 or more gain Raid 1." Vader is a 5/5
#// costing 6, so without the "Other" his own aura would stack onto his printed Raid 1 and he would
#// attack for 5+2 = 7. The correct total is 5+1 = 6 — this section is what makes "Other" load-bearing.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1LEADER:DEPLOYED

---

# Deployed_OtherCost3Unit_StillGainsRaid1
#// HMW_007 DEPLOYED — the aura still applies to OTHER friendly cost-3+ units: the Super Battle Droid
#// (4/3, cost 3) attacks for 5. Vader is seeded deployed at ground index 1 (after the droid).

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_230:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# Deployed_Cost2Unit_StillExcluded
#// HMW_007 DEPLOYED — the cost gate is unchanged on the deployed side: the cost-2 Dark Trooper
#// attacks for its printed 3.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Deployed_ViaEpicAction_AuraStillApplies
#// HMW_007 — the REAL execution path: deploy through the Epic Action (6 resources), then a friendly
#// cost-3 unit attacks and still carries Raid 1 (4+1 = 5). Proves the aura is recomputed live from
#// the deployed leader unit, not just from a pre-seeded fixture.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_230:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:5

---

# ControlChange_StolenUnitGainsAuraFromNewController
#// HMW_007 — "friendly" is CONTROLLER-scoped, not owner-scoped. A Super Battle Droid OWNED by P2 but
#// CONTROLLED by P1 (who holds Vader) gains Raid 1 and attacks for 5.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: TWI_230:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# ControlChange_UnitUnderOpponentLosesAura
#// HMW_007 — the mirror: a droid OWNED by P1 (the Vader player) but CONTROLLED by P2 is NOT friendly
#// to Vader's controller, so it attacks P1's base for its printed 4.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArenaControlled: TWI_230:1

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Front_SpaceUnitAlsoGainsRaid1
#// HMW_007 — the aura names no arena, so a SPACE unit qualifies on cost alone. JTL_136 Prototype TIE
#// Advanced (4/3, cost 3) attacks P2's base for 4+1 = 5.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_136:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:HASKEYWORD:Raid

---

# DeployedVaderDefeated_FrontAuraResumes
#// HMW_007 — persistence across a state transition: a defeated leader UNIT returns to the leader zone
#// undeployed, and the FRONT aura ("Friendly units that cost 3 or more gain Raid 1") takes over again.
#// Vader is seeded deployed with 4 damage at ground index 1; he attacks the enemy 3/3 (dealing 5+1 = 6,
#// killing it) and takes 3 back onto his 4 existing damage = 7 vs 5 HP, so he dies and undeploys.
#// The Super Battle Droid then still attacks for 4+1 = 5, proving the aura is not tied to the arena unit.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:HMW_007:1:1:1:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_230:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:NOTDEPLOYED
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
