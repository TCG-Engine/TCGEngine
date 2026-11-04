# WhenPlayedFirst_ExperiencePerUpgrade_ThenHealPerFriendlyUpgrade
#// IC27_029 Chimaera, Grand Admiral's Might — Unit (Space) 8/8, cost 10, [Vigilance][Villainy],
#// Imperial/Vehicle/Capital Ship, unique.
#// Text: Shielded. When Played: Give an Experience token to each friendly unit for each upgrade on it. Then, heal 1
#//       damage from your base for each friendly upgrade.
#// COVERAGE: offer=N/A — "each", nothing is chosen (the Shielded/When Played order prompt is the generic entry-trigger
#//           ordering) · decline=N/A — no "may" · no target=NoUpgrades_NothingGivenNothingHealed ·
#//           quantity=this (2 upgrades → 2, 0 → 0; the heal counts AFTER the Experience lands: 4) ·
#//           order=ShieldFirst_ChimaeraCountsItsOwnShield (the player's trigger order changes both numbers) ·
#//           scope=EnemyUnitsAndTheirUpgrades_Ignored + MyUpgradeOnAnEnemyUnit_HealsButNoExperience +
#//           EnemyUpgradeOnMyUnit_ExperienceButNoHeal ("each upgrade on it" is any controller; "friendly upgrade" is not) ·
#//           EnemyGiven{Experience,Shield,Advantage}OnMyUnit_IsFriendly (CR 2.g: a TOKEN upgrade is controlled by the
#//           HOST's controller, whoever created it — one section per token primitive) ·
#//           value-class=this (a Shield token and a real upgrade both count) · clamp=Heal_ClampsAtZero ·
#//           reqboundary=RequestBoundary_BeforeTheTriggerOrder · control=N/A — When Played only, fixed resolver ·
#//           modes=2P,TeamSuns ("each friendly unit", "friendly upgrade") — TeamSuns_TeammatesUnitsAndUpgradesCount ·
#//           TwinSuns=N/A (no player reference beyond "your base").
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Readings: the per-unit count is taken for every unit
#// BEFORE any token is given (one instruction, not a running count); "friendly upgrade" = an upgrade a friendly player
#// CONTROLS, on any unit — a non-token upgrade is controlled by whoever played it, even on an enemy unit (CR 2.f), a
#// token upgrade by the host's controller (CR 2.g); the heal counts after the Experience tokens land ("Then").
#//
#// THE POSITIVE (When Played resolved first). Imperial Dark Trooper carries Academy Training + a Shield: 2 Experience
#// (4 upgrades). Battlefield Marine has none: nothing. Chimaera has no Shield yet: nothing. 4 friendly upgrades → base
#// 10 → 6. Then Shielded gives Chimaera its Shield.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:CARDID:IC27_029
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1BASEDMG:6

---

# ShieldFirst_ChimaeraCountsItsOwnShield
#// Same board, Shielded resolved first: Chimaera has 1 upgrade and gets 1 Experience; the Trooper gets 2. Friendly
#// upgrades: 4 + 2 = 6 → base 10 → 4.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1BASEDMG:4

---

# NoUpgrades_NothingGivenNothingHealed
#// No upgrades anywhere (When Played first): no Experience, no heal — the base stays on 10.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:10

---

# EnemyUnitsAndTheirUpgrades_Ignored
#// "each FRIENDLY unit" / "each FRIENDLY upgrade": P2's Marine with P2's Academy Training gets nothing and is not
#// counted. P1's Trooper with one Experience gets 1 more → 2 friendly upgrades → base 10 → 8.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASEDMG:8

---

# MyUpgradeOnAnEnemyUnit_HealsButNoExperience
#// P1 first plays Academy Training on P2's Marine (its only legal host; cost 2 + 2 off-aspect). That upgrade is FRIENDLY
#// to P1 — it counts for the heal (base 10 → 9) — but the Marine is not a friendly unit, so it gets no Experience.
## GIVEN
CommonSetup: bbk/ggw/{myResources:14;myhandCardIds:SOR_120,IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:9

---

# EnemyUpgradeOnMyUnit_ExperienceButNoHeal
#// P2 plays Academy Training on P1's Trooper (P1's only unit, so its only legal host). "each upgrade on IT" counts it, so
#// the Trooper gets 1 Experience — but P2's upgrade is not friendly, so the heal counts only that Experience: 10 → 9.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10;theirResources:2;theirhandCardIds:SOR_120}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASEDMG:9

---

# EnemyGivenExperienceOnMyUnit_IsFriendly
#// CR 2.g: "A player owns and controls any token upgrades attached to units they control." P2's C-3PO (IC27_070) gives an
#// Experience token to P1's Trooper — that TOKEN is P1's, so it is a friendly upgrade. The Trooper gets 1 more; 2 friendly
#// upgrades heal 2: 10 → 8. (A token stamped with its CREATOR as controller reads 1 → 9.)
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: IC27_070:1:0
## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASEDMG:8

---

# EnemyGivenShieldOnMyUnit_IsFriendly
#// Same rule for a Shield: P2 plays Moment of Peace (SOR_073) on P1's only unit. It counts for P1's heal: 1 Experience +
#// the Shield = 2 → base 10 → 8.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10;theirResources:3;theirhandCardIds:SOR_073}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASEDMG:8

---

# EnemyGivenAdvantageOnMyUnit_IsFriendly
#// Same rule for an Advantage token: P2's Danger Squadron Wingmen (ASH_157) attacks P1's base and its On Attack gives the
#// Advantage to P1's Trooper. 1 Experience + the Advantage = 2 → base 10 (+4 from the attack = 14) → 12.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: ASH_157:1:0
## WHEN
- P2>AttackSpaceArena:0:BASE
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASEDMG:12

---

# Heal_ClampsAtZero
#// 4 friendly upgrades heal at most the 2 damage on the base: it ends on 0.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1BASEDMG:0

---

# RequestBoundary_BeforeTheTriggerOrder
#// The trigger-order answer arrives in a later request; the When Played still counts and heals as in the positive.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1BASEDMG:6

---

# TeamSuns_TeammatesUnitsAndUpgradesCount
#// TEAM SUNS: teammate P3's Marine (1 Experience) is a friendly unit — it gets 1 more — and its upgrades are friendly:
#// 2 → base 10 → 8. Enemy seat 2's Marine (1 Experience) gets nothing and is not counted.
## GIVEN
CommonSetup: bbk/ggw/{myResources:10;myhandCardIds:IC27_029;myBaseDamage:10}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:SOR_T01
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P3GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:8
