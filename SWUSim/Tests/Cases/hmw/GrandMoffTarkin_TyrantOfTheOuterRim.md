# DeployedLeaderWithSpaceOverrideEntersSpaceArena
#// HMW_004's deployed side is The Death Star, a SPACE unit — the first leader whose deployed
#// arena differs from the default. Deploy must consult the leaderUnitArena override
#// (LeaderDeployArena), not the plain CardTargetArena default.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myResources:9}

## WHEN
- P1>DeployLeader

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# DeployedLeaderFixtureSeedsTheOverrideArena
#// The myLeaderDeployed FIXTURE must agree with a real deploy: it seeds the arena
#// LeaderDeployArena picks, not a hardcoded ground zone. Without this, any test that seeds a
#// deployed Tarkin would contradict the engine it is testing.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# DeployedLeaderHasDeployedSideTraits
#// The deployed side is a different printed face: The Death Star is an Imperial Vehicle Capital
#// Ship, NOT the leader side's Imperial Official. The leaderUnitTrait override REPLACES the
#// leader row's traits rather than adding to them.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASTRAIT:Vehicle
P1SPACEARENAUNIT:0:HASTRAIT:Capital Ship
P1SPACEARENAUNIT:0:HASTRAIT:Imperial
P1SPACEARENAUNIT:0:NOTTRAIT:Official

---

# FortifyUpgradeIgnoresTheAspectPenalty
#// "Ignore the aspect penalties on upgrades with Fortify you play." Tarkin provides Vigilance + Villainy
#// and the `g` base provides Command, so HMW_171 Trap Field (cost 2, Aggression + Heroism) has BOTH pips
#// uncovered: 2 + 4 = 6 normally. On exactly 2 resources it can only attach if the waiver drops the whole
#// penalty, so the attach IS the assertion. (Baseline for the unwaived case:
#// keywords/Fortify.md::AFortifyUpgradePaysTheOffAspectPenalty.)

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:2;myhandCardIds:HMW_171}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_171
P1RESAVAILABLE:0

---

# NonFortifyUpgradeStillPaysTheAspectPenalty
#// The waiver is scoped to upgrades WITH Fortify. SOR_166 Infiltrator's Skill is a cost-1 Aggression
#// upgrade with no Fortify, so Aggression stays uncovered: 1 + 2 = 3, unaffordable on 2 resources, which
#// makes PlayHand a silent no-op. Without the Fortify scoping it would attach for 1.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:2;myhandCardIds:SOR_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# DeployedRegroupDefeatsAnEnemyBaseAtTenOrLess
#// Deployed side: "When the regroup phase starts: You may defeat a base with 10 or less remaining HP."
#// P2's base is a 30-HP colour base at 25 damage = 5 remaining, so it qualifies; P1's own base is
#// undamaged (30 remaining) and must NOT be offered. Defeating a base means its controller loses, so
#// this ends the game — there is no separate "defeated base" state (CR 3.2.5).
#// Both decks are stocked so the regroup DRAW deals no deck-out damage (that alone would end the game
#// from 25 and hand a false pass).

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN

---

# DeployedRegroupMayDefeatYourOwnBase
#// The printed text says "a base" with no friendly/enemy qualifier, so YOUR OWN base is a legal target
#// when it is at 10 or less remaining HP — and defeating it makes YOU lose. Legal, not advisable.
#// Here only P1's base qualifies (25 damage = 5 remaining), so P2 wins.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:myBase-0

## EXPECT
P2WIN

---

# DeployedRegroupDeclineLeavesTheBaseAlive
#// It is a "may" — declining must leave the base exactly as it was and the game running.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:25

---

# DeployedRegroupElevenRemainingIsNotOffered
#// The threshold is "10 or less remaining HP", so 11 remaining (30 - 19) must not qualify. Proven by
#// driving the regroup all the way through to the next action phase: an unoffered prompt lets the two
#// ResourcePasses reach PHASE:MAIN, while a wrongly-offered base-defeat would sit in front of them and
#// strand the game in the regroup. Guards a > / >= slip on the boundary.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:19}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:19
PHASE:MAIN

---

# DeployedRegroupExactlyTenRemainingIsOffered
#// The inclusive edge: 10 remaining (30 - 20) qualifies.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:20}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN

---

# UndeployedTarkinHasNoRegroupBaseDefeat
#// The base-defeat clause is printed only on the DEPLOYED side (The Death Star). An undeployed Tarkin
#// keeps the aspect waiver but must offer nothing at the regroup phase — so the enemy base survives even
#// though it sits at 5 remaining HP; the regroup runs straight through to the next action phase.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:25
PHASE:MAIN

---

# FourSeats_RegroupDefeatsTheCHOSENSeatsBase
#// HMW_004 deployed — "You may defeat a base with 10 or less remaining HP." Unqualified, so every base at
#// the table that meets the threshold is offered. Here BOTH P2 (25 damage on a 30-HP base) and P4 (20 on
#// a 25-HP base) qualify, and P1 names P4. Two legacy shapes die on this: the offer used to be the literal
#// pair ['myBase-0','theirBase-0'], so p4Base-0 was not even a candidate; and the applier used to collapse
#// any non-"my" pick onto OtherPlayer(), which would eliminate P2 instead. For an ability whose entire
#// effect is "that player is out of the game", guessing the seat is the worst possible failure.
#// Only P4 leaves, so its TEAM (2 and 4) still has a live seat — the game must not be over.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:20
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:p4Base-0

## EXPECT
SEATCOUNT:4
SEATLIVE:4:false
SEATLIVE:2:true
SEATLIVE:1:true
NOGAMEWINNER

---

# Deployed_IsTheDeathStar_TheTarkinDoctrineNoLongerSeesATarkin
#// The deployed face is titled "The Death Star", not "Grand Moff Tarkin". HMW_206 The Tarkin Doctrine's
#// "If you control Grand Moff Tarkin" is therefore FALSE once he has deployed: no -3/-0 on the enemy.
#// (The undeployed case is TheTarkinDoctrine.md::WhenPlayed_WithTarkin_GivesEnemyMinus3_NoSelfExhaust.)
#// Leader zone objects stay in the leader zone after a deploy, so a title check that reads the leader row
#// must read the DEPLOYED face.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1NODECISION

---

# Undeployed_ProvidesVigilanceAndVillainy
#// The leader's aspects cover a card: SOR_033 Death Trooper (Vigilance/Villainy, 3) under a no-aspect base
#// (JTL_031 Lake Country) costs exactly 3 — no penalty. (Its When Played hits the only friendly ground unit,
#// itself, for 2; there is no enemy ground unit.)

## GIVEN
CommonSetup: nbk/rrk/{myLeader:HMW_004;myResources:3;myhandCardIds:SOR_033}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_033
P1RESAVAILABLE:0

---

# Deployed_StillProvidesVigilanceAndVillainy
#// …and the deployed Death Star still provides them.

## GIVEN
CommonSetup: nbk/rrk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:3;myhandCardIds:SOR_033}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_033
P1RESAVAILABLE:0

---

# Undeployed_LeaderTraits_C3PO_ImperialOrOfficialOnly
#// LAW_152 C-3PO: "On Attack: You may give an Experience token to another non-leader unit that shares a
#// Trait with a friendly leader." The undeployed Tarkin is Imperial/Official:
#//   myGroundArena-1 SOR_128 Imperial Trooper      → in
#//   myGroundArena-2 TS26_53 Official              → in
#//   myGroundArena-3 LAW_228 Vehicle Speeder       → out
#//   myGroundArena-4 SOR_164 Creature              → out
#//   mySpaceArena-0  JTL_251 Vehicle Capital Ship  → out

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004}
P1OnlyActions: true
WithP1GroundArena: [LAW_152:1:0 SOR_128:1:0 TS26_53:1:0 LAW_228:1:0 SOR_164:1:0]
WithP1SpaceArena: JTL_251:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2

---

# Deployed_LeaderTraits_C3PO_ImperialVehicleOrCapitalShip
#// Deployed, the friendly leader is The Death Star — Imperial/Vehicle/Capital Ship. The Official-only unit
#// drops out; the Vehicle and the Capital Ship come in. The Death Star itself is a LEADER unit, so it is
#// never a pick ("non-leader").

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [LAW_152:1:0 SOR_128:1:0 TS26_53:1:0 LAW_228:1:0 SOR_164:1:0]
WithP1SpaceArena: JTL_251:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-3&mySpaceArena-0

---

# ThreeSeats_RegroupBaseDefeat_HealsTheEliminatorFive
#// REPORTED 2026-09-28 (game 1400002, a 4-seat Twin Suns): "when this game hits the regroup phase and the
#// Death Star defeats a player, it doesn't heal 5 as it should."
#//
#// CR §12.6.2 (Twin Suns): "When a player eliminates another player from the game (such as by being the
#// last player to damage the eliminated player's base), that player heals 5 damage from their own base,
#// after resolving player elimination as specified in 11.3." It is a FORMAT rule, not card text — nothing
#// on HMW_004 mentions healing, which is why this looked like a missing card ability and is not one.
#//
#// ⚠ THE HEAL ALREADY WORKED FOR COMBAT (twinsuns/Phase5.md::HealFiveOnKO). SWUEliminateSeat heals
#// whenever it is handed a $killer, and CombatLogic passes the damager. The Death Star's route does not:
#// SWUDefeatBase() fills the damage in and delegates to the SHARED state-based sweep
#// SWUCheckBaseDefeatState(), which hardcodes SWUEliminateSeat($p, null) because a genuine SHRINK defeat
#// has no damager. So an ability elimination with a perfectly attributable eliminator lost its attribution.
#//
#// ⚠ FIXTURE GAP, STATED PLAINLY: GameStateBuilder cannot seed a DEPLOYED leader on seats 3/4 (it is
#// "undeployed only" for far seats), so Tarkin sits on SEAT 1 here while the VICTIM is a far seat. In the
#// reported game Tarkin was on seat 3. That is acceptable because the defect is in killer ATTRIBUTION,
#// which reads no seat arithmetic — SWUTeamOf is the seat itself outside a team game — and the far-seat
#// TARGET path is already pinned by FourSeats_RegroupDefeatsTheCHOSENSeatsBase above.
## GIVEN
#// P1's base carries 10 damage so a 5-point heal is VISIBLE. It must also stay ABOVE the 10-remaining
#// threshold or the Death Star would offer P1's own base too and change what is being measured.
CommonSetup: grw/ggk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
#// 30 - 27 = 3 remaining, the only base at or under 10, so it is the only thing offered.
WithP3Base: SOR_019:27
#// ⚠ Stock EVERY deck. CommonSetup leaves them empty and the regroup DRAW then decks a seat out for base
#// damage, which silently moves the very number this section asserts.
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:p3Base-0
## EXPECT
SEATLIVE:3:false
#// The whole point: 10 damage - 5 healed = 5.
P1BASEDMG:5

---

# FourSeats_RegroupBaseDefeat_HealsTheEliminatorFive
#// The reported seat count. Per the owner rule that Twin Suns needs BOTH 3P and 4P, this repeats the
#// section above at four seats, where the victim is P4 and two other seats are still live — so the game
#// is NOT down to a last survivor and the heal has to land during a still-running game.
#// Free-for-all on purpose (no WithTeams): CR §12.6.2 lives in the Twin Suns section, and the same-team
#// carve-out in SWUEliminateSeat is a separate house ruling that this section must not accidentally test.
## GIVEN
CommonSetup: grw/ggk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:27
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:p4Base-0
## EXPECT
SEATLIVE:4:false
SEATLIVE:2:true
SEATLIVE:3:true
P1BASEDMG:5

---

# RegroupBaseDefeat_OwnBase_HealsNobody
#// CONTROL, and the carve-out is printed in the rule itself: "If a player eliminates themself through an
#// ability, no player heals damage from their base this way." HMW_004 says "a base" with no qualifier, so
#// P1 may pick its OWN — and must get nothing for it.
#// This must pass BEFORE and AFTER the fix; it is what stops the fix from healing on every base defeat.
#//
#// ⚠ P1's OWN base cannot be asserted here. Eliminating a seat runs _SWUEliminationCleanup, which removes
#// its base, and `P1BASEDMG` then FATALS with "No base for player 1" rather than failing — so the obvious
#// assertion is not merely wrong, it crashes the runner. Measure the absence of a heal on the SURVIVORS
#// instead, on two DISTINCT damage values so a heal credited to the wrong seat is still visible (the same
#// trick twinsuns/ConcedeEliminatesInsteadOfEndingTheGame.md uses).
## GIVEN
#// Bases pinned explicitly: P1 at 3 remaining is the ONLY thing at or under 10, so it is the only offer,
#// and the survivors sit at 22 and 23 remaining — damaged enough to measure, far above the threshold.
CommonSetup: grw/ggk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBase:SOR_019;myBaseDamage:27;theirBase:SOR_019;theirBaseDamage:8}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:7
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:myBase-0
## EXPECT
SEATLIVE:1:false
#// Untouched: a self-elimination credits its heal to nobody.
P2BASEDMG:8
P3BASEDMG:7

---

# RegroupBaseDefeat_EndsTheGameAtTHATRegroupsEnd_NotAnActionPhaseLater
#// CR §12.7.1: "Once one player is eliminated, the game will end once THE CURRENT PHASE ends. The player
#// with the most HP remaining on their base at the end of the current phase wins." It says "current phase"
#// twice and never "action phase" — and §12 has no Regroup Phase subsection at all, so the general rules
#// carry (§12.1.2 / §11.1.3). The Death Star is the format's one elimination that fires at REGROUP start,
#// so it is the only card that can tell the two readings apart.
#//
#// This pins the boundary in BOTH directions, which is the whole point — an assertion that a winner exists
#// "eventually" would pass for an engine that waits a full extra action phase:
#//   • immediately after the elimination, still inside the regroup: NO winner yet.
#//   • once the regroup actually completes: the winner is declared, at THAT boundary.
#// P3 is eliminated, P1 heals to 5 damage (CR §12.6.2), P2's base is untouched — so P2 has the most
#// remaining HP and wins. The heal is deliberately load-bearing on the OUTCOME here, not just on a number.
#//
#// ⚠ NOT COVERED BY twinsuns/Phase5.md. Its scoring sections reach the boundary with a bare
#// `ScorePhaseEnd` after a synthetic `EliminateSeat`, which never runs a real regroup — so the
#// ActionPhaseStart half of the deferred scoring (the half a regroup elimination depends on) was untested.
## GIVEN
CommonSetup: grw/ggk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:27
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:p3Base-0
#// Finish the regroup. These two ResourcePasses are what carry the game over the boundary into
#// ActionPhaseStart, where _SWUScoreTwinSunsEndOfPhase() scores the phase that just ended.
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
SEATLIVE:3:false
GAMEWINNERS:2
#// Scoring lives at the TOP of ActionPhaseStart(), so "the action phase never began" shows up as it never
#// announcing itself: the banner, the initiative flip and SetTurnPlayer all sit below the game-over early-out.
LOGCOUNT:0:— Action Phase —

---

# RegroupBaseDefeat_NoWinnerYetWhileStillInsideTheRegroup
#// The other half of the pair above, split out so a regression names WHICH direction broke: the elimination
#// alone must NOT end the game. §12.7.1 defers to the end of the phase, and §12.7.2 still expects
#// "at the end of the phase" abilities and "for this phase" expiries to resolve first — so scoring early
#// would skip them. The game is over one step later; here it must not be.
## GIVEN
CommonSetup: grw/ggk/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:27
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:p3Base-0
## EXPECT
SEATLIVE:3:false
NOGAMEWINNER
