# WhenDefeated_MostPowerfulFriendly_GetsExperience
#// IC27_056 Allegiant General Pryde — Heartless Efficiency (2/2 Ground, Command/Villainy, First Order·Official)
#// "When Defeated: Give an Experience token to the friendly unit with the most power. (If multiple units are
#// tied, choose one.)"
#// IC27_056#0 (a CUSTOM — the pick is mandatory) reads every friendly unit's CURRENT power (the team; Pryde has
#// already left play) and offers only the units tied for the most. One leader auto-resolves; a tie prompts.
#//
#// COVERAGE: offer=Tie_PromptsBetweenTheTiedUnitsOnly ·
#//           decline=N/A (no "you may") ·
#//           boundary=WhenDefeated_MostPowerfulFriendly_GetsExperience (4 vs 3 — one leader) paired with
#//           Tie_PromptsBetweenTheTiedUnitsOnly (3 vs 3) ·
#//           control=N/A (no owner-scoped zone; the resolver is his controller at defeat) ·
#//           reqboundary=Tie_AnsweredAcrossRequestBoundary ·
#//           modes=2P,TeamSuns (text says "friendly") · TeamSuns=TeamSuns_TeammatesUnitCanBeTheMostPowerful
#//
#// Pryde attacks a 4/7 and dies. Knight of Ren (4) beats the Imperial Dark Trooper (3) and auto-gets the token.
#// The enemy Industrious Team (4) is not friendly.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: LOF_084:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_084
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Tie_PromptsBetweenTheTiedUnitsOnly
#// Two 3-power units tie for the most (the 2-power TIE/ln Fighter does not): the pick offers exactly the two.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Tie_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# CurrentPower_NotPrinted
#// "most power" is CURRENT power: a Battlefield Marine (printed 3) with two Experience tokens (5) beats a
#// printed-4 Knight of Ren.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01
WithP1GroundArenaUpgrade: 1:SOR_T01
WithP1GroundArena: LOF_084:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# NoFriendlyUnitLeft_NothingHappens

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# DeployedLeaderUnit_CanBeTheMostPowerful
#// Value class: a deployed leader unit (Grand Moff Tarkin, 2/7 deployed) competes like any unit — here the
#// Knight of Ren (4) still wins.

## GIVEN
CommonSetup: ggk/bbw/{myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: LOF_084:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_084
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# TeamSuns_TeammatesUnitCanBeTheMostPowerful
#// Team Suns: "friendly" is the team — teammate P3's Knight of Ren (4) beats P1's Imperial Dark Trooper (3).
#// Pryde is defeated by an event (SOR_078 Vanquish played by P1 on its own unit) so the board stays simple.

## GIVEN
CommonSetup: ggk/bbw/{myResources:7}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SOR_078
WithP1GroundArena: IC27_056:1:0
WithP1GroundArena: SEC_080:1:0
WithP3GroundArena: LOF_084:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
