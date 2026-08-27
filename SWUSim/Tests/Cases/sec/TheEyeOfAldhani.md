# NextActionPhase_PayOrExhaust
#// SEC_073 The Eye of Aldhani (Event, cost 1, Vigilance, Innate/Trick)
#//   "At the start of the next action phase, for each enemy unit, its controller must pay 1 resource or
#//    exhaust that unit."
#// P1 plays Eye of Aldhani, then both players pass to regroup and on into the NEXT action phase. There,
#// P2 (the enemy of the caster) gets one MZMULTICHOOSE over its 2 units, capped at its 1 ready resource:
#// it pays 1 to keep SOR_095 ready; the unselected SEC_080 is exhausted. P2 ends with 0 ready resources.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 1

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2RESAVAILABLE:0

---

# ChooseNothing_AllExhausted
#// SEC_073 The Eye of Aldhani — at the next action phase, the enemy player may pay 1 resource per unit to
#//   keep it ready. Declining (choosing nothing) exhausts ALL of P2's units and spends no resources. P2's
#//   two ground units both end exhausted; its 2 resources stay ready.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 2

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2RESAVAILABLE:2

---

# FewerResourcesThanUnits_CapAtResources
#// SEC_073 The Eye of Aldhani — the keep-ready choice is capped at the enemy's ready resources. P2 has 4
#//   units (3 ground + 1 space) but only 3 resources, so it can keep at most 3 ready. It pays for the 3
#//   ground units (all ready, 0 resources left); the unpaid space unit is exhausted.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2Resources: 3

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:READY
P2SPACEARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:0

---

# NoEnemyUnits_NoOp
#// SEC_073 The Eye of Aldhani — with no enemy units at the next action phase, the delayed effect resolves
#//   to nothing (no decision is offered to P2).

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2NODECISION

---

# TwoCopies_TwoSeparateResolutions
#// SEC_073 The Eye of Aldhani — playing TWO copies in a phase queues TWO separate next-action pay-or-exhaust
#// resolutions (not one collapsed). P2 has a single unit and only 1 ready resource: it pays 1 to keep SOR_095
#// ready through the FIRST resolution, but then cannot pay for the SECOND (0 resources left), so SOR_095 is
#// exhausted. (Bug: the stacks collapsed to one resolution, leaving SOR_095 ready.)

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_073
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 1

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:0

---

# UnitsPlayedAFTERTheEventAreAlsoTaxed
#// SEC_073 The Eye of Aldhani — the delayed effect enumerates "each enemy unit" when it RESOLVES at the
#// start of the next action phase, not when the event is played. P2 controls nothing when P1 plays it,
#// plays SOR_095 afterwards, and that unit is still taxed next phase: P2 pays 1 of its 4 (re-readied)
#// resources to keep it ready, ending on 3. Untaxed, P2 would have kept all 4.

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2Resources: 4
WithP2Hand: SOR_095
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:3

---

# BothOpponentsGetTheirOwnPrompt
#// ⚠ REGRESSION GUARD, live bug 2026-08-27 — the OtherPlayer() determined-seat family.
#// "For each ENEMY unit, ITS CONTROLLER must pay 1 resource or exhaust that unit." At 3+ seats the enemy
#// units span EVERY opponent of the caster, and each controller decides for their OWN units with their
#// OWN resource cap. The resolution targeted OtherPlayer($armer) — a single seat — so with the Eye armed
#// by seat 1, seat 2 was taxed and seat 4 was left completely untouched (measured).
#//
#// The per-opponent body was always correct; only the fan-out was wrong. Both opponents now get their own
#// independent prompt. The picks are deliberately ASYMMETRIC (P2 keeps slot 0, P4 keeps slot 1) — a
#// symmetric pair would still pass if the two prompts were resolved against each other's frame.
## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 1
WithP4GroundArena: SOR_095:1:0
WithP4GroundArena: SEC_080:1:0
WithP4Resources: 1
## WHEN
- P1>PlayHand:0
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P4>ResourcePass
- P2>AnswerDecision:myGroundArena-0
- P4>AnswerDecision:myGroundArena-1
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2RESAVAILABLE:0
P4GROUNDARENAUNIT:0:EXHAUSTED
P4GROUNDARENAUNIT:1:READY
P4RESAVAILABLE:0

---

# Seat3Armer
#// ⚠ REGRESSION GUARD, live bug 2026-08-27 — the twin of the section above, for the OTHER half of the
#// same defect. The resolution loop ran `for ($armer = 1; $armer <= 2; …)`, so an Eye armed by seat 3 or 4
#// never resolved AT ALL — the card was a dead blank for half the table. Here seat 3 arms it and both of
#// its opponents (2 and 4 — teammates are never enemies) are taxed.
## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 2
WithP3Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 0
WithP4GroundArena: SOR_095:1:0
WithP4Resources: 0
## WHEN
- P3>PlayHand:0
- P4>Pass
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P4>ResourcePass
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:EXHAUSTED
P4GROUNDARENAUNIT:0:EXHAUSTED
