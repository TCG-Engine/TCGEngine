# CheapUnitInPlay_OpponentDiscardsTheirOnlyCard
#// HMW_154 Dooku's Solar Sailer - Droid Army Portent (Aggression/Villainy, cost 3, 3/3 Space, unique,
#// Separatist/Vehicle/Transport) — "When Played: If you control a unit that costs 1 or less, each
#// opponent discards a card from their hand."
#// COVERAGE: offer=OpponentWithTwoCards_ChoiceIsQueuedOnTHEIRSeat (the discard MZCHOOSE is the only
#//           decision this card raises; its pool is the shared forced-discard "myHand" ZONE param, which
#//           carries no card-specific restriction — so the assertable half is WHOSE seat holds it, and
#//           WHICH card the answer actually discards) ·
#//           negative=NoOtherUnits_SailerItselfDoesNotSatisfyTheGate + OnlyACostTwoUnit_GateNotMet +
#//           EnemyCheapUnit_DoesNotCount (three different ways the gate can be false) ·
#//           boundary=OnlyACostOneUnit_GateMet vs OnlyACostTwoUnit_GateNotMet (the 1-vs-2 pair;
#//           TokenUnitCostsZero_GateMet covers the low side) ·
#//           control=ControlChanged_StolenCheapUnitCountsForItsNewController (owner P2, controller P1 —
#//           the gate reads CONTROL, and the mirror EnemyCheapUnit_DoesNotCount asserts the other seat) ·
#//           reqboundary=RequestBoundary_AcrossTheOpponentsDiscardChoice ·
#//           decline=N/A — nothing here is optional: the gate is an "If", not a "you may", and the
#//           opponent's discard is forced. CannotDo is covered separately by OpponentEmptyHand_NoOp.
#// ⚠ COST IS ALWAYS PRINTED. A Token Unit's printed cost is 0, so it satisfies "costs 1 or less";
#//   a deployed leader unit's is its leader card's printed cost (4+ across every set), which is why
#//   there is no leader-unit section — it can never reach 1 or less.
#// ⚠ The Sailer itself costs 3 and is already in play when its own When Played resolves, so it can
#//   never satisfy its own gate. NoOtherUnits_... is the section that pins that.
#// Aspects: P1 is rrk (Aggression base + Vader Aggression/Villainy), so the Sailer is fully on-aspect
#// and costs its printed 3.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_154
P1RESAVAILABLE:1
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P2DISCARDUNIT:0:FROM:HAND
P1NODECISION
P2NODECISION

---

# OnlyACostOneUnit_GateMet
#// HMW_154 — the boundary's upper half, stated on its own so its partner below is a one-line diff.
#// SOR_128 Death Star Stormtrooper costs exactly 1: "1 or less" includes 1.
#// (Same board as the first section; kept separate so the pair reads as a pair.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0
WithP2Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080

---

# OnlyACostTwoUnit_GateNotMet
#// HMW_154 — the boundary's lower half and the load-bearing NEGATIVE. SEC_080 Imperial Dark Trooper
#// costs 2, one over the line. Without this an implementation that never reads the cost at all (or
#// reads "2 or less") passes every positive section in this file.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArena: SEC_080:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_154
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P2DISCARDCOUNT:0
P1NODECISION
P2NODECISION

---

# NoOtherUnits_SailerItselfDoesNotSatisfyTheGate
#// HMW_154 — the Sailer is in play by the time its own When Played resolves, and it costs 3. With no
#// other friendly unit on the board the gate must be false. An implementation that scans the arena
#// without a cost check, or that counts "any unit you control", fires here.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_154
P1GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION
P2NODECISION

---

# TokenUnitCostsZero_GateMet
#// HMW_154 — the value-CLASS variant. A Token Unit is a unit you control, and its printed cost is 0,
#// so it satisfies "costs 1 or less". TWI_T01 Battle Droid is the only friendly unit besides the
#// Sailer, so the discard can only be coming from the token.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArena: TWI_T01:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095

---

# EnemyCheapUnit_DoesNotCount
#// HMW_154 — "if YOU CONTROL a unit that costs 1 or less". The cheap unit is on the OPPONENT'S board,
#// so the gate is false. Paired with ControlChanged_... below, this is what proves the scan is
#// controller-scoped rather than a board-wide sweep.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP2GroundArena: SOR_128:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION
P2NODECISION

---

# ControlChanged_StolenCheapUnitCountsForItsNewController
#// HMW_154 — owner ≠ controller. SOR_128 is OWNED by P2 but CONTROLLED by P1, so it is a unit P1
#// controls and the gate opens. The mirror of EnemyCheapUnit_DoesNotCount: same card, same cost,
#// opposite answer, and the only thing that differs is who controls it.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArenaControlled: SOR_128:2
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095

---

# OpponentWithTwoCards_ChoiceIsQueuedOnTHEIRSeat
#// HMW_154 — the opponent picks. With 2+ cards in hand SWUDiscardCards QUEUES an MZCHOOSE on the
#// OPPONENT'S queue instead of resolving inline, which is the branch every cross-player ordering bug
#// lives in; a fixture that leaves the opponent holding 0 or 1 cards never reaches it. Asserted in two
#// halves: the decision is pending on P2 and NOT on P1, and the answer discards the card P2 named
#// (Confiscate) while the one they kept is still in hand.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0
WithP2Hand: [SOR_095 SOR_251]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-1
- P2>Drain

## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_251
P2DISCARDUNIT:0:FROM:HAND
P1NODECISION
P2NODECISION

---

# OpponentDiscardChoicePendsOnP2_NotP1
#// HMW_154 — the offer half of the section above, left PENDING. The forced-discard prompt belongs to
#// the discarding player; a handler that queued it on the caster's seat would still "work" in a test
#// that answers with a bare AnswerDecision, and would deadlock in a real game.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0
WithP2Hand: [SOR_095 SOR_251]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Choose_card_to_discard
P1NODECISION
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# RequestBoundary_AcrossTheOpponentsDiscardChoice
#// HMW_154 — the request-boundary cell. The queued discard is answered in a FRESH process, so anything
#// the When Played held in an in-memory global between raising the choice and resolving it would be
#// empty by the time P2 answers. Identical GIVEN/WHEN/EXPECT to
#// OpponentWithTwoCards_ChoiceIsQueuedOnTHEIRSeat, plus one SimulateRequestBoundary line before the
#// answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0
WithP2Hand: [SOR_095 SOR_251]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:myHand-1
- P2>Drain

## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_251
P1NODECISION
P2NODECISION

---

# OpponentEmptyHand_NoDiscardNoPrompt
#// HMW_154 — cannot-do, which is a different branch from the gate being false: here the gate IS met,
#// there is simply nothing to discard. It must resolve as a clean no-op with no dangling decision on
#// either seat (the empty-hand prompt family — SEC_186 Garindan et al).

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_154
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_154
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION
P2NODECISION
