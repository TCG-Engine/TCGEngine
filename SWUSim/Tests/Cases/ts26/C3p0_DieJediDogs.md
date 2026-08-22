# OpponentUsesActionDealsPower
#// TS26_15 C-3P0 — Action [Exhaust]: deal damage equal to this unit's power (2) to another ground unit;
#// "Only opponents may use this ability."
#//
#// ⚠⚠ REWRITTEN 2026-08-24 UNDER A USER RULING, and this section previously encoded the WRONG player.
#// "Opponents" means opponents of the unit's CURRENT CONTROLLER, not of its owner — ownership is
#// irrelevant to the gate. C-3P0's own When Played hands control to an opponent, so after that the
#// CONTROLLER is barred and everyone else may fire it, including the original owner.
#// At TWO seats the two readings name OPPOSITE players, which is why this is a correction to
#// Premier/Eternal and not only to Twin Suns:
#//     old (owner-gate):      P2 — the controller — fired it.
#//     new (controller-gate): P1 — the original owner — fires it.
#// The old expectation was simply incorrect, so it is replaced rather than preserved (a deliberate I1
#// exception; see the sweep plan §2b).
#//
#// P1 plays C-3P0 → P2 takes control (auto-targeted: one opponent, so no prompt). Both pass; the ready
#// phase readies C-3P0 under P2. Next round P1 activates him — from P1's frame he is theirGroundArena-0 —
#// exhausting him as the cost and dealing 2 to another ground unit P2 controls.

## GIVEN
CommonSetup: gbw/rrk/{handCardIds:TS26_15;myResources:6}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseUnitAbility:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:TS26_15
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# TheDamageScalesWithHisCURRENTPower
#// TS26_15 C-3PO — "deal damage equal to THIS UNIT'S POWER", read live rather than from the printed 2.
#// ⚠⚠ REWRITTEN 2026-08-24 UNDER A USER RULING. The previous version's comment asserted that ownership
#// decided who may fire the ability ("placing him with a plain WithP2GroundArena would make P2 the OWNER,
#// and then nobody could use the ability at all"). That is WRONG: the gate reads the CURRENT CONTROLLER
#// and ignores Owner entirely, so a plainly-placed C-3PO is fine and every opponent of his controller may
#// fire him.
#// C-3PO sits in P2's arena under P2's control. Three Experience tokens put him at 5 power. P1 — an
#// opponent of the controller — activates him and SOR_046 takes 5.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: TS26_15:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# TwinSuns_CasterChoosesWhichOpponentTakesControl
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT), covering CLAUSE 1 ONLY.
#// "When Played: AN OPPONENT takes control of this unit" — the caster picks which. OtherPlayer() picked
#// one silently, so on a four-seat table the caster could not choose who to hand a 2/5 body (and a
#// political weapon) to. No $eligible filter: any live opponent can take control of a unit.
#// ⚠ The unit is carried by UID, not a positional mzID: the pick is interactive and the arena can shift
#//   before the continuation runs.
#// P1 plays C-3PO and gives him to SEAT 3. He must leave P1's arena and appear on SEAT 3's board — not
#// seat 2's, which is where the old code always sent him — while remaining OWNED by P1 (that Owner is what
#// the "only opponents may use" gate reads).
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#//
#// ⚠⚠ CLAUSE 2 IS NOT COVERED HERE AND IS DELIBERATELY UNCHANGED. "Only opponents may use this ability"
#// — opponents of WHOM? The user ruled (2026-08-23) for opponents of the CURRENT CONTROLLER (the CR
#// reading), which INVERTS the two sections above (OpponentUsesActionDealsPower and
#// TheDamageScalesWithHisCURRENTPower), both of which encode the owner reading explicitly in their
#// fixtures AND their comments. Those edits are pending user sign-off; the gate still implements the owner
#// reading, so this section says nothing about who may fire the Action.
#// Mutation check: revert clause 1 to OtherPlayer() and this reds.

## GIVEN
CommonSetup: gbk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: TS26_15
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:TS26_15
P2GROUNDARENACOUNT:0

---

# TwinSuns_HeBACKFIRES_AnyOpponentOfTheControllerFiresHim
#// ⚠ THE SEAT-COUNT CELL for CLAUSE 2 — added 2026-08-24, from the user's own scenario.
#// "Only opponents may use this ability" = opponents of the CURRENT CONTROLLER (USER RULING). Ownership is
#// irrelevant. So once C-3P0 has been handed to an opponent, EVERY other seat may fire him — not just the
#// player who gave him away, and not the controller.
#//
#// THE POINT OF THE CARD, and why it is a Twin Suns politics card: P1 plays C-3P0 and must hand him to an
#// opponent. P1 picks SEAT 4. On the next action phase SEAT 3 — a player with no involvement in that
#// exchange at all — may fire him, and chooses to shoot P1's own ground unit. Playing C-3P0 BACKFIRES on
#// the player who played him.
#// ⚠ Under the old owner-gate this was impossible twice over: the gate barred by OWNER (so only non-owners
#//   could fire, i.e. not P1 but also nothing about P3 specifically), and the action was never OFFERED on a
#//   unit the actor did not control until TS26_15 was registered in $anyPlayerUnitActions.
#// ⚠ SEAT 3 is deliberately neither the owner nor the controller — with P1 or P4 acting, an owner-gate and
#//   a controller-gate can coincide. Seat 3 separates them.
#// Mutation check: revert the gate to Owner and this reds; unregister $anyPlayerUnitActions['TS26_15'] and
#// the offer disappears.

## GIVEN
CommonSetup: gbw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_046:1:0
WithP4GroundArena: TS26_15:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P3>UseUnitAbility:p4GroundArena-0
- P3>AnswerDecision:p1GroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
P4GROUNDARENAUNIT:0:CARDID:TS26_15
P4GROUNDARENAUNIT:0:EXHAUSTED

---

# TwinSuns_OfferedToANonControllerButNOTToTheController
#// ⚠ THE OFFER-PATH CELL — added 2026-08-24 with the new P#UNITACTIONS assertion, and it is what finally
#// makes C-3PO's gate testable. Every other section drives `UseUnitAbility` DIRECTLY, which bypasses the
#// offer list, so before this assertion existed unregistering TS26_15 from $anyPlayerUnitActions failed
#// NOTHING — the ability would have been invisible and unclickable in a real game with a green suite.
#// "Only opponents may use this ability" = opponents of the CURRENT CONTROLLER (USER RULING), so the list
#// must contain C-3PO for a NON-controller and must NOT contain him for the controller.
#// Here SEAT 4 controls him and SEAT 3 is active: seat 3 must be offered p4GroundArena-0.
#// ⚠ SWUComputeActionsData only computes while the seat is ACTIVE, which is why the controller-side
#//   assertion needs its own section below rather than living here.
#// Mutation check: unregister $anyPlayerUnitActions['TS26_15'] and this reds.

## GIVEN
CommonSetup: gbw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_046:1:0
WithP4GroundArena: TS26_15:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN

## EXPECT
SEATCOUNT:4
P3UNITACTIONSHAS:p4GroundArena-0

---

# TwinSuns_TheCONTROLLERIsNotOfferedHisAction
#// ⚠ THE GATE CELL, via the offer path. The controller is barred by "only opponents may use this ability",
#// so C-3PO must NOT appear in his OWN controller's unit-action list.
#// SEAT 4 controls him and is ACTIVE (required — SWUComputeActionsData computes nothing for an inactive
#// seat, so a NOT-assertion on an inactive seat would pass vacuously and prove nothing).
#// ⚠ This is the offer-path half of the owner-vs-controller ruling: reverting the gate in
#//   SWUUnitActionAffordable to `Owner` makes C-3PO appear here (seat 4 is the controller but NOT the
#//   owner in the general case), which is precisely the bug the ruling corrected.
#// Mutation check: revert the TS26_15 gate to Owner and this reds.

## GIVEN
CommonSetup: gbw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 4
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_046:1:0
WithP4GroundArena: TS26_15:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN

## EXPECT
SEATCOUNT:4
P4UNITACTIONSNOT:myGroundArena-0
