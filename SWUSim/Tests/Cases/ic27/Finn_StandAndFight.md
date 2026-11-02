# Upgraded_ByExperience_AttackHealsTwo
#// IC27_037 Finn — Stand and Fight (4/5 Ground, Vigilance/Heroism, Resistance, unique)
#// "Sentinel. While this unit is upgraded, he gains Restore 2."
#// Sentinel is a printed keyword (auto-wired, generic coverage). The work is the self-conditional Restore 2,
#// read live in GetConditionalKeyword_Restore_Value through _SWUIsUpgraded.
#//
#// COVERAGE: offer=N/A (a continuous self-conditional keyword — nothing is chosen anywhere on the card) ·
#//           decline=N/A (no "you may"; Restore is not optional) ·
#//           boundary=Upgraded_ByExperience_AttackHealsTwo paired with NotUpgraded_AttackHealsNothing
#//           (zero vs one upgrade) ·
#//           control=N/A (no owner-scoped zone: Restore heals the base of the unit's controller when it attacks,
#//           which is the generic Restore keyword, not this card) ·
#//           reqboundary=UpgradeRemoved_ThenAttack_NoRestore + UpgradePlayed_ThenAttack_Restores (each crosses
#//           one SimulateRequestBoundary between the action that changes the upgrades and the attack) ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "upgraded" is about this unit)
#//
#// Finn (upgraded by one Experience token, 5/6) attacks the enemy base. Restore 2 heals P1's base 5 -> 3.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:5
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Restore:2

---

# NotUpgraded_AttackHealsNothing
#// The gate's FALSE case: no upgrade, no Restore — the same attack leaves P1's base on 5.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ShieldToken_IsAnUpgrade_Restores
#// Value class: a Shield token is a token UPGRADE, so it makes Finn "upgraded" exactly like a real upgrade.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:4

---

# CapturedCard_IsNotAnUpgrade_NoRestore
#// A captured card is a face-down subcard, NOT an upgrade. Finn guarding a captive is not upgraded.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1GroundArenaCaptive: 0:SEC_080

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore

---

# Upgraded_AttacksAUnit_StillRestores
#// Restore fires on EVERY attack ("when this unit attacks"), not just base attacks. Upgraded Finn attacks
#// a 3/7 Consular Security Force; P1's base still heals 2.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# UpgradeRemoved_ThenAttack_NoRestore
#// The ENDING: the condition is read live. Finn starts upgraded with Academy Training (+2/+2); P1 plays
#// Confiscate to defeat it, then (after a request boundary) attacks. He is no longer upgraded: no heal.
#// He swings for his printed 4.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:5
P2BASEDMG:4

---

# UpgradePlayed_ThenAttack_Restores
#// The STARTING edge, and the dispatch path a real game takes: Finn enters the action unupgraded, P1
#// plays Academy Training (+2/+2, Command — off-aspect under this leader/base, so 2 + 2) onto him, and
#// after a request boundary he attacks as a 6/7 with Restore 2.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:3
P2BASEDMG:6

---

# Blanked_Upgraded_NoRestore
#// A Finn that has lost all abilities (SOR_138 Force Lightning's marker) has no Restore even while
#// upgraded — and no Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_037:1:0:SOR_138
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:5
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
