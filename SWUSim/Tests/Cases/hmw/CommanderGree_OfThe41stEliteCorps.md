# ThreeCommandIconsOnUnits_RaidFour
#// COVERAGE: offer=N/A (STRUCTURAL: a constant keyword grant) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=this section (3 icons) paired with TwoIcons_NoRaid (2)
#//           control=N/A (the count reads friendly CONTROL; no owner-scoped zone)
#//           reqboundary=N/A (STRUCTURAL: live read)
#//           quantity=UpgradeIconsCount (an upgrade's icon, not only unit icons) · negative=EnemyIconsDoNotCount
#//           modes=2P,TeamSuns (text says "friendly units ... and upgrades") — TeamSuns_TeammatesUnitCounts
#//
#// HMW_138 Commander Gree — Unit (Ground) 3/6, cost 4, [Command], Republic/Clone/Trooper.
#// "While there are 3 or more Command aspect icons among friendly units (including this one) and upgrades,
#//  this unit gains Raid 4."
#// Gree [Command] + SOR_095 [Command][Heroism] + SEC_080 [Command][Villainy] = 3 Command icons.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0 SEC_080:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4

---

# TwoIcons_NoRaid

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0 SOR_046:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:0

---

# UpgradeIconsCount
#// Gree + SOR_095 = 2 unit icons; SOR_120 Academy Training [Command] on SOR_095 is the third.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:SOR_120

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4

---

# EnemyIconsDoNotCount
#// P2's SEC_080 carries SOR_120 — two enemy Command icons, neither friendly.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0]
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:0

---

# RaidFour_WhileAttacking

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:POWER:3

---

# TeamSuns_TeammatesUnitCounts
#// Gree + SOR_095 on seat 1, SEC_080 on teammate seat 3: friendly, so 3 icons.

## GIVEN
CommonSetup: ggw/bbk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [HMW_138:1:0 SOR_095:1:0]
WithP3GroundArena: SEC_080:1:0

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
