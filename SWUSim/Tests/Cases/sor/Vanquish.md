# DefeatsNonLeaderUnit
#// SOR_078 Vanquish (Event, cost 5) — "Defeat a non-leader unit." P2's only unit is
#// a non-leader (Battlefield Marine, SOR_095), so it is the sole target and is
#// auto-defeated.
#// COVERAGE: offer=Offer_ExcludesLeaderUnits_AndSpansBOTHSides (three legal targets seated, decision
#//           left pending, P1SELECTABLEEXACT asserts the exact pool) + ControlTakenUnit_... (pool
#//           narrowed to one, so auto-resolution IS the assertion) · reqboundary=SimulateRequestBoundary_
#//           TargetPickSurvives · control=ControlTakenUnit_DefeatedCardGoesToItsOWNERsDiscard (owner
#//           differs from controller; CR 8.4 files the card by OWNER) · boundary=N/A — Vanquish has no
#//           quantity, threshold or duration: it DEFEATS outright, so there is no N vs N±1 pair to
#//           discriminate. The equivalent discrimination is the non-leader gate
#//           (Offer_ExcludesLeaderUnits_... proves a leader unit is out of the pool, and
#//           OnlyLeaderUnitsInPlay_... proves the gate can empty the pool entirely) · decline=N/A —
#//           the printed text carries no "you may" and the pick is a mandatory MZCHOOSE, which
#//           SWUValidateDecisionAnswer refuses a PASS on; the only no-effect branch is an EMPTY pool,
#//           covered by OnlyLeaderUnitsInPlay_NoTarget_EventIsStillPlayedAndPaid.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:SOR_078}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0

---

# Offer_ExcludesLeaderUnits_AndSpansBOTHSides
#// SOR_078 Vanquish — "Defeat a NON-LEADER unit." Two halves of the target pool, asserted together
#// rather than answered (an answer proves the branch, never the pool):
#//   * "non-leader" is load-bearing — P2's DEPLOYED leader unit is in the ground arena and must NOT be
#//     offered. Deployed leaders seat at the END of the arena, so it is theirGroundArena-2.
#//   * "a unit" carries no controller word, so it spans BOTH sides — P1's own Battlefield Marine is a
#//     legal target of P1's own Vanquish.
#// Three legal targets are seated so the pick stays interactive; the decision is deliberately left
#// PENDING (EXPECT evaluates at END state, so an offer assert must not be consumed by an answer).
#// Intended: exactly myGroundArena-0, theirGroundArena-0, theirGroundArena-1.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# DefeatsFriendlyUnit_TargetWordIsUnqualified
#// SOR_078 Vanquish — the resolution half of the offer above: "a non-leader unit" names no controller,
#// so P1 may point their own Vanquish at their own Battlefield Marine. It is defeated (P1 ground 1 → 0)
#// and both enemy units are untouched. Discriminates against an implementation that silently scopes the
#// pool to the opponent. P1's discard holds 2 — Vanquish itself plus its own defeated Marine.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:3
P1DISCARDCOUNT:2

---

# DefeatsChosenEnemyNonLeader_LeaderUnitUntouched
#// SOR_078 Vanquish — same board, the other branch: P1 answers the SECOND enemy non-leader
#// (theirGroundArena-1, Consular Security Force 3/7 — HP is irrelevant, Vanquish DEFEATS rather than
#// damages). P2 keeps their other non-leader AND their deployed leader unit, proving the defeat is
#// applied to the ANSWERED unit and not to index 0 or to the whole arena.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1

---

# OnlyLeaderUnitsInPlay_NoTarget_EventIsStillPlayedAndPaid
#// SOR_078 Vanquish — the no-valid-target branch. The ONLY unit on the table is P2's deployed leader
#// unit, which "non-leader" excludes, so the pool is empty and no decision is raised at all.
#// Per the standing ruling that an action which fizzles still pays its cost, Vanquish is nonetheless
#// played: 5 resources spent (0 ready left), the card in P1's discard, and the leader unit alive.
#// Guards against both an empty MZCHOOSE hanging the action and a "no targets" path that refunds.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078;theirLeaderDeployed:true}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P2GROUNDARENACOUNT:1

---

# ControlTakenUnit_DefeatedCardGoesToItsOWNERsDiscard
#// SOR_078 Vanquish × a control change. P1 CONTROLS an Imperial Dark Trooper that P2 OWNS (the end
#// state after a take-control effect). It is the only unit on the table, so it is the sole legal
#// non-leader target and the pick auto-resolves — the auto-resolution IS the offer assertion here.
#// Per CR 8.4 a defeated card goes to its OWNER's discard pile, not its controller's: P2's discard
#// gains the Dark Trooper while P1's holds only Vanquish itself. An implementation that files a
#// defeated unit by CONTROLLER passes every same-seat section and fails only this one.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_078
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080

---

# SimulateRequestBoundary_TargetPickSurvives
#// SOR_078 Vanquish — in production the "defeat a non-leader unit" MZCHOOSE ENDS the request: the
#// answer arrives in a fresh process where every non-serialized global is empty. Mirrors
#// DefeatsChosenEnemyNonLeader_LeaderUnitUntouched with the boundary inserted before the answer, so the
#// pending pick and its DEFEAT_UNIT continuation must both survive a gamestate round-trip.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myhandCardIds:SOR_078;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
