# DeployedPassive_ActiveWhenDeployed
#// Krennic's passive is active on both leader and leader-unit side.
#// When Krennic is deployed in the arena, another friendly damaged unit still gets +1/+0.
#// COVERAGE (leader — both sides): FRONT = the +1/+0 aura + the Epic Action; DEPLOYED = Restore 2 +
#//           the same aura, which now also covers the leader unit itself.
#//           boundary=Front_DamageBoundary_ZeroUnbuffedOneBuffed (0 damage vs 1) +
#//           EpicDeploy_FourResourcesIsOneTooFew / EpicDeploy_FiveResourcesIsEnough (4 vs 5
#//           resources) + Deployed_KrennicBuffsHimselfOnceDamaged vs DeployedUnit_Restore2 (leader
#//           unit at 2 damage reads 3 power, undamaged attacks for 2) ·
#//           control=StolenDamagedUnit_BuffedByItsNewController ("friendly" is read from the
#//           CONTROLLER — a unit owned by the opponent is inside the aura) ·
#//           reqboundary=DeployedAuraSurvivesRequestBoundary ·
#//           offer=N/A — neither side ever builds a target pool: the aura is an untargeted continuous
#//           effect over "each friendly damaged unit" and the Epic Action deploys THIS leader, so
#//           there is nothing to choose. Front_EnemyDamagedUnitIsNotBuffed stands in for the scope
#//           assertion an offer would otherwise carry ·
#//           decline=N/A — the aura is not optional and the Epic Action is a player-initiated action
#//           that is simply not taken; EpicDeploy_FourResourcesIsOneTooFew is the refusal branch.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001:1:1:1
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# DeployedUnit_Restore2
#// Krennic leader unit side has Restore 2.
#// P1 base starts at 4 damage. Krennic (power 2) attacks P2 base,
#// heals 2 from P1 base -> P1 base at 2; P2 base takes 2.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001:1:1:1;
  myBaseDamage:4
}
SkipPreGame: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# Front_DamageBoundary_ZeroUnbuffedOneBuffed
#// SOR_001 Director Krennic (Vigilance/Villainy leader) — FRONT side, the only printed passive: "Each
#// friendly DAMAGED unit gets +1/+0." Boundary pair in one board: two identical SOR_095 (3/3), one at
#// 0 damage and one at 1. Krennic is NOT deployed, so this also proves the aura is live from the
#// leader zone. The undamaged copy stays at power 3; one point of damage is the whole trigger and
#// takes the other to 4.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:3

---

# Front_EnemyDamagedUnitIsNotBuffed
#// SOR_001 Director Krennic — "each FRIENDLY damaged unit". The scope exclusion: P1's damaged 3/3 gets
#// its +1 while the OPPONENT's identically damaged 3/3 stays at printed power 3. P2's leader is Sabine
#// Wren, so nothing on that side could supply the buff.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:POWER:3

---

# Front_BuffAppearsTheMomentTheUnitTakesCombatDamage
#// SOR_001 Director Krennic — the aura is continuous, not a one-shot applied on entry. SOR_046 (3/7)
#// starts undamaged at power 3 and attacks a 3/1: it kills the 3/1 with its printed 3 and takes 3
#// back, and having become "damaged" it now reads power 4 without anything re-applying the aura.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:4

---

# EpicDeploy_FourResourcesIsOneTooFew
#// SOR_001 Director Krennic — "Epic Action: If you control 5 OR MORE resources, deploy this leader."
#// Boundary pair, low side: with exactly 4 resources the deploy is refused outright — the leader stays
#// undeployed, the ground arena stays empty, the Epic Action is still available and no resource is
#// spent. (The Epic Action has no resource COST; the 5 is a condition on what you control.)

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4

---

# EpicDeploy_FiveResourcesIsEnough
#// SOR_001 Director Krennic — boundary pair, high side. One more resource than the refused case and
#// the same command deploys him: a leader unit appears in the ground arena, the Epic Action is spent,
#// and all 5 resources are still ready because the condition is a check, not a payment.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1RESAVAILABLE:5

---

# Deployed_KrennicBuffsHimselfOnceDamaged
#// SOR_001 Director Krennic — DEPLOYED side. "Each friendly damaged unit" has no "other", so the
#// deployed Krennic unit is inside his own aura: at 2 damage his printed 2 power reads 3. The
#// undamaged half of this boundary is DeployedUnit_Restore2 above, where the same leader unit attacks
#// for exactly 2.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001:1:1:1:2
}
SkipPreGame: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:3

---

# StolenDamagedUnit_BuffedByItsNewController
#// SOR_001 Director Krennic — CONTROL CHANGE. "Friendly" is read from the CONTROLLER, not the owner: a
#// 3/7 sitting in P1's arena but OWNED by P2 (the end state after a take-control effect) is inside
#// Krennic's aura the moment it is damaged. The enemy 3/1 attacks it, dies to the 3 it takes back, and
#// the stolen unit ends damaged at power 4 even though its owner has no Krennic.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: LAW_180:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:4

---

# DeployedAuraSurvivesRequestBoundary
#// SOR_001 Director Krennic — REQUEST BOUNDARY. The aura is re-derived every read from the leader-zone
#// object, so after a serialization round-trip the DEPLOYED leader must still be found as the source:
#// the damaged friendly 3/3 still reads power 4 and the damaged leader unit still reads 3.

## GIVEN
CommonSetup: bbk/grw/{
  myLeader:SOR_001:1:1:1:2
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>SimulateRequestBoundary
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:3
