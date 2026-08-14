# Buff_AnotherAllyCostsExactlyThree_PlusTwoPower
#// HMW_107 Stormtrooper Patrol — cost 3, [Command][Villainy], Ground 2/4, Imperial/Trooper.
#// Text: "Sentinel (…)  /  While you control another unit that costs 3 or more, this unit gets +2/+0."
#// The Sentinel half is keyword-only and auto-wired from $Sentinel_Cards (asserted here as a cheap
#// guard, not a separate section). The conditional buff is the real work: a continuous self-passive in
#// ObjectCurrentPower, gated on ANOTHER unit YOU control whose PRINTED cost is >= 3.
#//
#// COVERAGE: offer=N/A (continuous passive, no target choice) · decline=N/A (no optional branch)
#//           boundary=Buff_AnotherAllyCostsExactlyThree_PlusTwoPower + NoBuff_OnlyAllyCostsTwo_BoundaryBelow
#//           control=NoBuff_EnemyUnitCostingFour ("you control" scoping — an enemy 4-cost grants nothing)
#//           reqboundary=N/A (no state written across a decision)
#//
#// Boundary UPPER half: SOR_063 Cloud City Wing Guard costs exactly 3 → the buff applies.
#// +2/+0 is POWER ONLY, so HP stays at the printed 4.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 SOR_063:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_107
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoBuff_OnlyAllyCostsTwo_BoundaryBelow
#// Boundary LOWER half: SEC_080 Imperial Dark Trooper costs 2 — one under the threshold — so the buff
#// must NOT apply. Without this pair the positive alone passes for any threshold value.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 SEC_080:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_107
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:4

---

# NoBuff_Alone_SelfCostThreeDoesNotCount
#// "ANOTHER unit" — the load-bearing self-exclusion. Stormtrooper Patrol itself costs 3, so a naive
#// "you control a unit costing 3+" check would buff a lone copy. It must not.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_107:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:2

---

# Buff_TwoCopies_EachBuffsTheOther
#// HMW_107 is non-unique. Two copies each satisfy the OTHER's condition (each costs 3), so BOTH are
#// buffed. Discriminates a correct per-object UID self-exclusion from a global "any copy in play" flag.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 HMW_107:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4

---

# NoBuff_EnemyUnitCostingFour
#// "While YOU CONTROL another unit…" — control scoping. An ENEMY 4-cost unit (SOR_046 Consular
#// Security Force) satisfies the cost test but not the control test, so no buff.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_107:1:0
WithP2GroundArena: SOR_046:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# NoBuff_TokenAllyCostsZero
#// Value-CLASS variant: a TOKEN unit is a unit you control, but a Battle Droid token (TWI_T01) has a
#// printed cost of 0, so it must not switch the buff on.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 TWI_T01:1:0]

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:2

---

# Buff_SpaceAllyCounts_NoArenaRestriction
#// The text says "another unit", with no arena qualifier — so a SPACE unit counts for a GROUND
#// Stormtrooper Patrol. (Contrast the Sentinel reminder text, which IS arena-scoped.)
#// JTL_069 Munificent Frigate costs 5.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_107:1:0
WithP1SpaceArena: JTL_069:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# Buff_DeployedLeaderUnitCounts
#// Value-CLASS variant: a DEPLOYED leader is a unit you control, and every leader's printed cost is
#// well above 3 — so it satisfies the condition. Exercises the live-object read rather than a
#// printed-CardType "Unit" filter (which would exclude a leader unit, whose CardType is "Leader").

## GIVEN
CommonSetup: ggk/rrk/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: HMW_107:1:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_107
P1GROUNDARENAUNIT:0:POWER:4

---

# Buff_EndsWhenTheAllyLeavesPlay
#// Duration/ENDING cell: the buff is continuous while the ally is in play, so it must DROP the moment
#// the ally leaves. SOR_063 (2/4) enters with 2 damage and attacks SOR_046 (3/7): it deals 2 and takes
#// 3 back, reaching 5 damage on 4 HP → defeated. Stormtrooper Patrol is then alone and back to 2 power.
#// A permanent-buff bug passes every positive section above and fails only here.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 SOR_063:1:2]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_107
P1GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Buff_AppliesToCombatDamage
#// Proves the buff flows through the real combat path, not just the display/stat read: the buffed
#// 4-power Patrol hits the enemy base for 4, not its printed 2.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_107:1:0 SOR_063:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4