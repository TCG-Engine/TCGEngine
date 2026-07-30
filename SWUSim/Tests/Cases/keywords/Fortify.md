# FixtureSeedsABaseUpgrade
#// The harness must be able to seed an upgrade onto a base and observe it. Uses HMW_095 Carbonite
#// Chamber, a real Fortify card.

## GIVEN
CommonSetup: grw/grw/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_095
P2BASE:UPGRADECOUNT:0

---

# BaseUpgradeCountReflectsAttachments
#// Exercises the same Subcards read the UpgradeCount virtual and SWUBaseUpgradeCount perform, through
#// the engine rather than at the source level. Two upgrades so the count is not confusable with a bool.

## GIVEN
CommonSetup: grw/grw/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095,HMW_081

## WHEN

## EXPECT
P1BASE:UPGRADECOUNT:2
P1BASE:UPGRADE:0:CARDID:HMW_095
P1BASE:UPGRADE:1:CARDID:HMW_081
P2BASE:UPGRADECOUNT:0

---

# PlayingAFortifyUpgradeAttachesItToYourBase
#// HMW_095 Carbonite Chamber costs 1 (single Vigilance pip). Setup bbw = Vigilance base + Luke
#// (Vigilance/Heroism), so it is ON-aspect and costs exactly 1 — proving the base-attach path adds no
#// surcharge of its own. There is no host prompt: "Attach this to your base" means the only legal host
#// is your own base.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;myhandCardIds:HMW_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_095
P1HANDCOUNT:0
P1RESAVAILABLE:2

---

# AFortifyUpgradePaysTheOffAspectPenalty
#// The baseline HMW_004 Grand Moff Tarkin later waives ("Ignore the aspect penalties on upgrades with
#// Fortify you play"). grw = Command base + Aggression/Heroism leader, so the single Vigilance pip on
#// HMW_095 is off-aspect: 1 + 2 = 3. Recording it now means Tarkin's waiver has something to change.

## GIVEN
CommonSetup: grw/grw/{myResources:3;myhandCardIds:HMW_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1RESAVAILABLE:0

---

# AFortifyUpgradeIsNotOfferedAUnitHost
#// With a friendly unit in play the Fortify upgrade must STILL attach to the base — the unit is not a
#// legal host, so no host prompt appears and the unit ends with no upgrades.

## GIVEN
CommonSetup: grw/grw/{myResources:3;myhandCardIds:HMW_095}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ANormalUpgradeStillAttachesToAUnitNotTheBase
#// The converse guard: a non-Fortify upgrade must never land on the base.
#// SOR_054 Jedi Lightsaber attaches to a non-Vehicle unit.

## GIVEN
CommonSetup: grw/grw/{myResources:5;myhandCardIds:SOR_054}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASE:UPGRADECOUNT:0

---

# ConfiscateCanDefeatABaseUpgrade
#// SOR_251 Confiscate — "Defeat an upgrade." A base upgrade IS an upgrade; if the shared host
#// enumerator (SWUGetUnitsWithUpgrades) skips bases, Fortify upgrades are unremovable by every card in
#// the game. Discard holds Confiscate itself (an event, discarded on play) + the defeated upgrade.

## GIVEN
CommonSetup: grw/grw/{myResources:4;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:0
P1DISCARDCOUNT:2

---

# BaseUpgradeDefeatFiresTheUpgradeDefeatedObserver
#// _SWUOnUpgradeDefeated sets SWU_FRIENDLY_UPGRADE_DEFEATED (read by ASH_039 / ASH_161). It is called
#// from the host-agnostic SWUDefeatUpgrade so this SHOULD already hold for a base host — but capture was
#// once a missed leave-play path for this exact observer, so assert it rather than assume it.

## GIVEN
CommonSetup: grw/grw/{myResources:4;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GLOBALEFFECT:SWU_FRIENDLY_UPGRADE_DEFEATED

---

# ASecondCopyOfAUniqueFortifyUpgradeDefeatsTheFirst
#// CR 8.19.1.b / 29.3 — you cannot control two copies of a unique card. HMW_206 The Tarkin Doctrine is
#// unique, so playing a second copy while one is attached to your base must defeat the older one.
#// SWUEnforceUpgradeUniqueness sweeps arena units and their subcards; without the base sweep the player
#// would illegally control two.

## GIVEN
CommonSetup: grw/grw/{myResources:8;myhandCardIds:HMW_206}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_206

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_206

---

# BaseUpgradesSurviveTheRequestBoundary
#// REGRESSION (found in a live game): a gamestate round-trip decodes Subcards as associative ARRAYS
#// (json_decode($x, true)), not objects. Every base-subcard reader that used $sub->CardID directly
#// therefore broke AFTER serialization while passing in-request: BaseUpgradeCardIDs hit
#// "(string)$array", emitting an "Array to string conversion" WARNING into the response that corrupted
#// the zone-data stream so the base did not render at all; the removal/uniqueness sweeps silently saw
#// no base upgrades. All of them now go through GetUpgradesOnUnit, which normalizes arrays to objects.

## GIVEN
CommonSetup: grw/grw/{myResources:4;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>SimulateRequestBoundary

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_095

---

# ConfiscateReachesABaseUpgradeAfterTheRequestBoundary
#// The teeth of the regression above: with array-shaped subcards, SWUGetUnitsWithUpgrades returned no
#// base host, so Confiscate silently fizzled and the upgrade was unremovable.

## GIVEN
CommonSetup: grw/grw/{myResources:4;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GLOBALEFFECT:SWU_FRIENDLY_UPGRADE_DEFEATED
