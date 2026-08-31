# CORE — while ANY seat owes a decision, EVERY kind of action is refused, from EVERY seat.
#
# `DecisionQueueController::AllQueuesEmpty()` is the table-wide interlock: nobody acts while anybody
# still owes an answer. It is asked at roughly ten separate sites — six `AllQueuesEmpty` guards in
# `CustomInput.php` (initiative, blast/plan counter, base action, resource smuggle, leader ability,
# unit ability), `ActionMap`'s own check for plays and attacks, and TurnController's PENDING_DECISION.
#
# ⚠⚠ ONLY THE `ActionMap` GATE IS TESTABLE FROM A FIXTURE, AND THAT IS A HARNESS LIMIT, NOT A CHOICE.
# GameTestAdapter routes playHand() and attack() through `ActionMap(...)` — "the same path as a real
# click" — so those two really do exercise the production guard. But claimInitiative, takeCounter,
# useUnitAbility and useLeaderAbility call `SWUTakeInitiative` / `SWUTakeCounter` / `SWUUnitAction` /
# `SWULeaderAction` DIRECTLY, skipping CustomInput.php and therefore skipping its `AllQueuesEmpty`
# guard. Measured: with seat 3 mid-decision, `P1>Claim` claims, `P1>TakeCounter:blast` takes, and
# `P1>UseUnitAbility` fires — all of which a real client is refused. The whole adapter file contains
# exactly ONE reference to AllQueuesEmpty.
#
# So the six CustomInput-layer interlocks (initiative, blast/plan counter, base action, resource
# smuggle, leader ability, unit ability) have NO fixture coverage and CANNOT be given any through this
# harness. Sections asserting them were written, failed, and were removed rather than left describing
# the harness instead of the engine — see the session notes. Closing this needs either the guard pushed
# down into the engine functions, or the adapter taught to mirror CustomInput; it is the same parity
# class `DevTools/tests/harness_action_open_parity_test.php` already polices for `_SWUOpenAction`.
#
# THE SETUP, borrowed from the hand-glow file because it is the only one that leaves a far seat
# deciding while the phase is still MAIN: JTL_237 TIE Bomber's "On Attack: deal 3 indirect damage to the
# defending player" hands SEAT 3 an assignment. The turn correctly stays with seat 1 (it never advances
# while a seat owes a decision), so seat 1 is the acting player and every attempt below must bounce.

---

# Baseline_TheFarSeatReallyIsDeciding
#// Establishes the fixture before anything is asserted about refusals: seat 3 owes the indirect
#// assignment, the phase is still MAIN, and the turn has stayed with seat 1. If this section ever
#// changes shape, every refusal section below is measuring something else.
## GIVEN
CommonSetup4P: rrk/bbw/bbw/bbw/{myResources:6}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP3GroundArena: [SOR_095:1:0]
## WHEN
- P1>AttackSpaceArena:0:P3B
## EXPECT
P3HASDECISION
PHASE:MAIN
TURNPLAYER:1

---

# PlayFromHandIsRefused
#// The gate `ActionMap` applies to a hand play — the one shape existing coverage already has, kept here
#// as the matrix's anchor so a change in the setup shows up against a known-covered case first.
## GIVEN
CommonSetup4P: rrk/bbw/bbw/bbw/{myResources:6}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1Hand: [SOR_128]
WithP3GroundArena: [SOR_095:1:0]
## WHEN
- P1>AttackSpaceArena:0:P3B
- P1>PlayHand:0
## EXPECT
P3HASDECISION
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# AttackIsRefused
#// `ActionMap`'s arena arm. Seat 1 keeps a SECOND, still-ready ground unit — the TIE Bomber that opened
#// the window is exhausted by its own attack, so without a fresh attacker this section would be
#// refused for the wrong reason and prove nothing.
## GIVEN
CommonSetup4P: rrk/bbw/bbw/bbw/{myResources:6}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1GroundArena: [SOR_046:1:0]
WithP3GroundArena: [SOR_095:1:0]
## WHEN
- P1>AttackSpaceArena:0:P3B
- P1>AttackGroundArena:0:P3B
## EXPECT
P3HASDECISION
P1GROUNDARENAUNIT:0:READY
P3BASEDMG:0

---

# OnceTheFarSeatAnswers_ActionsAreAllowedAgain
#// THE CONTROL, and without it every section above is worthless: "the action did not happen" is also
#// true of a fixture where the action was impossible for some unrelated reason.
#// ⚠ It is SEAT 2 that plays here, not seat 1. Seat 1 already spent its action on the attack; the turn
#// was merely being held open until seat 3 answered. Once it does, the action completes and the turn
#// passes normally — an earlier draft had seat 1 play again and failed, which was the fixture asserting
#// a free extra action rather than the interlock lifting.
## GIVEN
CommonSetup4P: rrk/bbw/bbw/bbw/{myResources:6}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2Hand: [SOR_128]
WithP2Resources: 8
WithP3GroundArena: [SOR_095:1:0]
## WHEN
- P1>AttackSpaceArena:0:P3B
#// ⚠ MZSPLITASSIGN answers are "<mzID>:<amount>" pairs and are written in the DECIDING seat's
#// own frame — myBase-0, not p3Base-0. The param reads "3|myGroundArena-0:3&myBase-0:3".
- P3>AnswerDecision:myBase-0:3
- P2>PlayHand:0
## EXPECT
P3NODECISION
P3BASEDMG:3
P2HANDCOUNT:0
P2GROUNDARENACOUNT:1
