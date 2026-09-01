# GivesShield
#// SOR_073 Moment of Peace (Event, cost 1) — "Give a Shield token to a unit."
#// P1's only unit (Battlefield Marine) is the sole target → auto-receives a shield.
#// COVERAGE: offer=Offer_IncludesEnemyAndShieldedUnits (pending SELECTABLEEXACT: friendly and
#//           ENEMY units, already-shielded included) · decline=N/A (the shield give is
#//           mandatory) · control=N/A (one-shot token give; enemy-unit targeting asserted in
#//           AlreadyShielded_GainsSecondShield) · boundary=0 shields → 1 (GivesShield) vs
#//           1 shield → 2 stacking (AlreadyShielded_GainsSecondShield) ·
#//           reqboundary=AlreadyShielded_GainsSecondShield (play and pick span separate requests)

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Offer_IncludesEnemyAndShieldedUnits
#// SOR_073 Moment of Peace — the target pool is ANY unit: P1's ground unit and P2's
#// already-shielded space unit are both offered (two candidates → the pick stays pending,
#// asserted here without answering).

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# AlreadyShielded_GainsSecondShield
#// SOR_073 Moment of Peace — shielding a unit that ALREADY has a Shield token stacks a
#// second one: P2's shielded Distant Patroller is chosen and ends with 2 Shields.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# NoUnitsInPlay_EventIsStillPlayedAndPaidFor
#// SOR_073 Moment of Peace — the NO-VALID-TARGET cell. "Give a Shield token to a unit" with no unit
#// anywhere on the table: the ability must return without queueing a decision, and the event is still
#// played — it lands in the discard and its resource is still spent. Per the standing ruling an action
#// that fizzles still pays its cost, so there is deliberately no "use it anyway?" confirmation here.
#// The three existing sections all run on boards with at least one legal target, so the empty-pool
#// early return is unexercised by them.
#// COVERAGE (addendum to the ledger in GivesShield): no-valid-target is covered here; the ledger's
#// reqboundary entry names AlreadyShielded_GainsSecondShield, which resolves the pick inside a single
#// request and does not actually cross a boundary — the real boundary case is
#// SimulateRequestBoundary_ShieldPickSurvivesTheBoundary below.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0

---

# SimulateRequestBoundary_ShieldPickSurvivesTheBoundary
#// SOR_073 Moment of Peace — the REQUEST-BOUNDARY cell, done with the harness directive rather than
#// claimed. Two units are on the table so the pick stays interactive (with one, it auto-resolves and no
#// request ever ends), and the boundary sits between the play and the answer: in production the choose
#// ends the request and the answer arrives in a fresh process, so anything the offer parked in memory
#// would be gone by then and the shield would silently never land.
#// P2's space unit is chosen and must end with exactly one Shield; P1's ground unit with none.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0    # 2nd legal target, keeps the choose interactive

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
