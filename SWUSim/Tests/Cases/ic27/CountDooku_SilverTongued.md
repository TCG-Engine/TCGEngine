# RegroupReady_TheOpponentChoosesOneGroundUnit
#// IC27_148 Count Dooku (Ground 8/7, cost 7, Cunning/Villainy) — "Each opponent can't ready more than 1 ground unit
#// and 1 space unit during the regroup phase. When Played: You may exhaust a unit."
#// P1 controls Dooku. P2 has three exhausted ground units: at the ready step P2 picks which ONE readies (Imperial
#// Dark Trooper, slot 1); the other two stay exhausted. P2's exhausted resources and leader-side are not units and
#// ready as normal.
#// The round: P1>Pass ends the action phase (P2 auto-passes under P1OnlyActions) → draw → each player's resource
#// pass → the ready step.
#// COVERAGE (regroup clause): offer=Offer_OnlyTheOpponentsExhaustedGroundUnits · negative=NoDooku_EveryUnitReadies +
#//           ControllersOwnUnitsReadyFreely + ActionPhaseReadiesAreNotLimited ·
#//           boundary=OnlyOneExhaustedPerArena_NoPrompt (1 = no limit felt) vs this section (3 → 1) ·
#//           value class=DeployedLeaderIsAGroundUnit (a leader unit counts) + AReadyUnitOrOneThatCantReady_UsesNoSlot ·
#//           arenas=OneGroundAndOneSpace (separate limits) · scope=resources here ·
#//           mid-regroup ready=AnEffectReadyDuringTheRegroupUsesTheSlot (Sneak Attack + Motti) ·
#//           duration=TheLimitAppliesEveryRegroup · control=ControlChange_TheNewControllersOpponentIsLimited ·
#//           reqboundary=AcrossARequestBoundary ·
#//           modes=TwinSuns_EachOpponentIsLimited ("each opponent" = a loop over live seats) +
#//           TeamSuns_ATeammateIsNotAnOpponent
#// COVERAGE (When Played): offer=WhenPlayed_OffersEveryUnit · take=WhenPlayed_ExhaustAnEnemyUnit ·
#//           decline=WhenPlayed_Decline · scope="a unit" is unqualified: WhenPlayed_CanExhaustAFriendlyUnit ·
#//           no target=N/A (Dooku himself is always a unit in play, so the pool is never empty)

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_046:0:0
WithP2Resources: 3:SOR_095:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:EXHAUSTED
P2RESAVAILABLE:3
P1NODECISION
P2NODECISION

---

# Offer_OnlyTheOpponentsExhaustedGroundUnits
#// The ready choice is left pending: it offers exactly P2's exhausted ground units. A unit that is already ready
#// is not offered (it does not "ready", CR 5.1.e), and the choice is P2's, not Dooku's controller's.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-2
P1NODECISION

---

# OneGroundAndOneSpace
#// The two limits are separate: P2 readies one ground unit AND one space unit, choosing each.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP2SpaceArena: SOR_225:0:0
WithP2SpaceArena: LOF_158:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:mySpaceArena-1

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:1:READY
P2NODECISION

---

# OnlyOneExhaustedPerArena_NoPrompt
#// One exhausted unit in each arena (plus an already-ready one): each readies at once, nothing is asked.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P2SPACEARENAUNIT:0:READY
P2NODECISION

---

# NoDooku_EveryUnitReadies
#// Control: the same P2 board with no Dooku in play — every exhausted unit readies, and nothing is asked.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP2SpaceArena: SOR_225:0:0
WithP2SpaceArena: LOF_158:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P2SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:1:READY
P2NODECISION

---

# ControllersOwnUnitsReadyFreely
#// "Each OPPONENT": Dooku's controller is not limited. P1's exhausted Dooku, two more ground units and two space
#// units all ready.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:0:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: SOR_225:0:0
WithP1SpaceArena: LOF_158:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:1:READY
P1NODECISION

---

# AReadyUnitOrOneThatCantReady_UsesNoSlot
#// SHD_193 Frozen in Carbonite's unit can't ready, so it is not a candidate and does not take the slot: P2's other
#// exhausted ground unit readies without a prompt; the frozen one stays exhausted.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArenaUpgrade: 0:SHD_193
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P2NODECISION

---

# DeployedLeaderIsAGroundUnit
#// A deployed leader is a ground unit and competes for the one slot. P1 plays Dooku and exhausts P2's deployed
#// leader with the When Played; at the ready step P2 chooses between the leader unit and Battlefield Marine, and
#// takes the Marine — the leader unit stays exhausted. (Fixture order: the Marine is slot 0, the leader unit slot 1.)
#// Were the leader unit no candidate it would ready alongside the Marine, so its staying exhausted is the proof.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7;theirLeaderDeployed:1}
P1OnlyActions: true
WithP1Hand: IC27_148
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2NODECISION

---

# AnEffectReadyDuringTheRegroupUsesTheSlot
#// "during the regroup PHASE", not just its ready step. P2 Sneak Attacks Admiral Motti (Sneak Attack 2 + 2 off-aspect =
#// 4; Motti 2 + 2 off-aspect - 3 = 1); at the start of the regroup Motti is defeated and his When Defeated readies P2's Imperial
#// Dark Trooper (Villainy). That was P2's one ground ready this regroup, so at the ready step Battlefield Marine
#// stays exhausted and nothing is asked. The SPACE slot is separate and still unspent: P2's exhausted TIE/ln readies.

## GIVEN
CommonSetup: yyk/bbw/{theirResources:5}
WithActivePlayer: 1
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_225:0:0
WithP2Hand: [SOR_219 SOR_226]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>AnswerDecision:myGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2SPACEARENAUNIT:0:READY
P2NODECISION

---

# ActionPhaseReadiesAreNotLimited
#// The limit is the regroup phase's only: in the action phase P1 plays SOR_169 Keep Fighting ("Ready a unit with 3
#// or less power", 2 + 2 off-aspect = 4) twice and readies BOTH of P2's exhausted ground units.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP1Hand: [SOR_169 SOR_169]
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:0

---

# TheLimitAppliesEveryRegroup
#// Not a one-shot, and the count starts over each regroup: P2 readies slot 1 in the first regroup, then readies
#// slot 0 in the second; slot 2 is still exhausted after both.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-1
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:EXHAUSTED
P2NODECISION

---

# ControlChange_TheNewControllersOpponentIsLimited
#// P2 controls P1's Dooku, so P1 is the opponent: P1 chooses one of its two exhausted ground units, while P2's own
#// exhausted units all ready.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP2GroundArenaControlled: IC27_148:1
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:READY
P1NODECISION
P2NODECISION

---

# AcrossARequestBoundary
#// The pending ready choice survives a request boundary.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>SimulateRequestBoundary
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P2NODECISION

---

# TwinSuns_EachOpponentIsLimited
#// Three seats, no teams; Dooku on seat 1. BOTH opponents are limited and each picks its own unit — asymmetric
#// picks (P2 slot 0, P3 slot 1) so two prompts resolved in each other's frame would fail.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP3GroundArena: SOR_095:0:0
WithP3GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P2>AnswerDecision:myGroundArena-0
- P3>AnswerDecision:myGroundArena-1

## EXPECT
SEATCOUNT:3
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P3GROUNDARENAUNIT:0:EXHAUSTED
P3GROUNDARENAUNIT:1:READY
P2NODECISION
P3NODECISION

---

# TeamSuns_ATeammateIsNotAnOpponent
#// Four seats, teams 1+3 vs 2+4; Dooku on seat 1. Seat 3 (teammate) readies both its units without a prompt; seat 2
#// (an opponent) is limited to one.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_148:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0
WithP3GroundArena: SOR_095:0:0
WithP3GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P4>ResourcePass
- P2>AnswerDecision:myGroundArena-1

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P3GROUNDARENAUNIT:0:READY
P3GROUNDARENAUNIT:1:READY
P2NODECISION
P3NODECISION

---

# WhenPlayed_ExhaustAnEnemyUnit
#// When Played: P1 exhausts P2's ready Battlefield Marine.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: IC27_148
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:IC27_148
P1RESAVAILABLE:0
P1NODECISION

---

# WhenPlayed_OffersEveryUnit
#// "a unit" is unqualified: both sides and both arenas, Dooku himself included.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: IC27_148
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: LOF_158:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# WhenPlayed_Decline
#// The "may": declining exhausts nothing.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: IC27_148
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:1
P1NODECISION

---

# WhenPlayed_CanExhaustAFriendlyUnit
#// The unqualified "a unit" reaches P1's own TIE/ln.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: IC27_148
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# AnEffectReadyAfterTheSlotIsSpent_IsRefused
#// Both players Sneak Attack an SOR_226 Admiral Motti ("When Defeated: You may ready a Villainy unit" — any side), so
#// two When Defeated readies resolve at the start of the regroup. P1's Motti resolves first and readies P2's first
#// Imperial Dark Trooper: a ready of P2's unit, so it spends P2's ONE ground ready. P2's Motti then tries to ready
#// P2's second Trooper and is refused — it stays exhausted through the ready step too (no slot left, nothing asked).
#// (P1: Sneak Attack 2, Motti 2 - 3 = 0 on-aspect. P2: Sneak Attack 2 + 2 = 4, Motti 2 + 2 - 3 = 1.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:2;theirResources:5}
WithActivePlayer: 1
WithP1GroundArena: IC27_148:1:0
WithP1Hand: [SOR_219 SOR_226]
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SEC_080:0:0
WithP2Hand: [SOR_219 SOR_226]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
P2NODECISION
