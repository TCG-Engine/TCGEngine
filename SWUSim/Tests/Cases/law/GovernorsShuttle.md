# EachPlayerDefeatsOwn
#// LAW_099 Governor's Shuttle (2/4) — When Played: each player chooses a unit they control. Defeat those
#// units. P1 picks its SEC_080 (keeps the Shuttle); P2 picks its SOR_046.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0

---

# MustChooseItselfWhenNoOtherUnits
#// LAW_099 Governor's Shuttle — "each player chooses a unit they control." With no other units in play, P1
#// controls only the newly-played Shuttle and must choose it; P2 controls nothing. The Shuttle is defeated.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# CanChooseFriendlyLeaderUnit
#// LAW_099 Governor's Shuttle — the chosen unit may be a leader unit; a defeated leader unit returns to
#// base rather than the discard. P1 chooses its deployed leader (returns to base, Shuttle survives); P2's
#// lone SOR_046 is auto-chosen and defeated.

## GIVEN
CommonSetup: brk/bgw/{myLeader:LAW_003:1:1:1;myResources:9}
WithActivePlayer: 1
WithP1Hand: LAW_099
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:NOTDEPLOYED
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0

---

# EachPlayersPoolIsByCONTROLNotByOwner
#// COVERAGE: offer=EachPlayersPoolIsByCONTROLNotByOwner (P1's pool asserted exactly while pending) +
#//           TheOpponentsPoolIsAlsoByCONTROLNotByOwner (P2's pool asserted exactly; it needs TWO
#//           P2-controlled units, because a lone candidate auto-parameterises and leaves no pool to read)
#//           · reqboundary=DefeatedUnitsGoToTheirOWNERSDiscard (a serialize round-trip on BOTH sides —
#//           before P1 answers and again before P2 answers) · control=all three sections below ·
#//           boundary=EachPlayerDefeatsOwn vs MustChooseItselfWhenNoOtherUnits (other units present /
#//           only the Shuttle) and CanChooseFriendlyLeaderUnit (a leader unit returns to base instead of
#//           the discard) · decline=N/A — "each player CHOOSES a unit they control" carries no "may", so
#//           the choose is mandatory and is queued non-declinable.
#// LAW_099 — "each player chooses a unit THEY CONTROL." Control, not ownership, scopes each pool, and this
#// board makes the two diverge on BOTH sides at once: SOR_046 sits in P1's ground arena but is OWNED by
#// P2, while SOR_095 sits in P2's ground arena but is OWNED by P1. P1's pool must therefore be exactly the
#// two units P1 CONTROLS — the P2-owned SOR_046 and the freshly played Shuttle — and must NOT reach the
#// SOR_095 that P1 owns but does not control. The decision is left pending so the pool itself is the
#// assertion; every pre-existing section seats owner == controller, where the two scopings agree.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArenaControlled: SOR_095:1
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# TheOpponentsPoolIsAlsoByCONTROLNotByOwner
#// LAW_099 — the opponent's half of "each player chooses" is scoped the same way, and it is queued in the
#// OPPONENT's frame rather than the caster's. P2 is given two units they CONTROL (SEC_080 they also own,
#// and the P1-owned SOR_095) so the choice stays a real pool rather than auto-resolving, while SOR_046 —
#// which P2 OWNS but which sits in P1's arena under P1's control — must be absent from it. The pool is
#// asserted from P2's own frame, so both entries read as myGroundArena-*; an owner-scoped pool would have
#// contained the far-side SOR_046 as theirGroundArena-0 instead.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaControlled: SOR_095:1
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# DefeatedUnitsGoToTheirOWNERSDiscard
#// LAW_099 — the follow-through: each player picks the unit they control, and each defeated unit goes to
#// its OWNER's discard, not to the discard of whoever controlled or chose it. P1 chooses the P2-owned
#// SOR_046 and P2 chooses the P1-owned SOR_095, so the two cards CROSS: P1's discard ends holding SOR_095
#// and P2's holds SOR_046, both arenas empty, and only the Shuttle survives in P1's space arena. A discard
#// routed by controller would have put each card back on the side it was standing on, which is exactly the
#// end state a same-owner board cannot distinguish. A serialize round-trip is inserted before EACH answer,
#// so the caster's parked choice has to survive the boundary and still be defeated once the opponent — a
#// different seat, on a later request — has answered.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArenaControlled: SOR_095:1
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P2>SimulateRequestBoundary
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_046

---

# CrossPlayerForcedChoice_ResolvesFromItsStoredParameter
#// LAW_099 — regression guard for the cross-player PASSPARAMETER hole.
#// When a player's "choose a unit you control" has exactly ONE legal target, SWUQueueChooseTarget emits
#// a PASSPARAMETER (a forced auto-resolve) rather than an MZCHOOSE. For the ACTING player that drains
#// inside the same request, but one queued for the OTHER seat stays PENDING until that seat's next
#// request — and in that window it was entirely unvalidated, so any submitted value was accepted and
#// the continuation acted on it. Measured before the fix: P2 could answer with a unit they did NOT
#// control and defeat it, and a garbage answer silently walked away from a MANDATORY choice.
#// This section pins the LEGAL path — P2 has exactly one controlled unit, so its forced choice must
#// resolve to that unit and nothing else. P1 keeps two candidates so P1's own pick stays a real choose.
#// ⚠ The REJECTION itself cannot be asserted in this DSL (an invalid answer throws rather than
#// producing an end state), so this guards that the forced resolve still works, not that bad input is
#// refused; the refusal is covered by the validator in GameLogic.php.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
