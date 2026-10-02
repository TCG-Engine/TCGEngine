# DiscardLookDiscard
#// TWI_223 Unmasking the Conspiracy (Event, Cunning) — "Discard a card from your hand. If you do, look at an
#// opponent's hand and discard a card from it." P1 discards SOR_095; then P2's only card is discarded.
## GIVEN
CommonSetup: yyk/bbw/{myResources:1;theirhandCardIds:SOR_128}
P1OnlyActions: true
WithP1Hand: [TWI_223 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK
- P1>AnswerDecision:theirHand-0
## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0

---

# TwinSuns4P_CasterPicksWhichOpponentsHand
#// TWI_223 — "… look at AN OPPONENT's hand and discard a card from it": the caster picks whose, AFTER the own
#// discard (the pick sits in the card's continuation). Found 2026-10-01 alongside the Spark of Rebellion report.
#// P1 picks P3; P2 — the seat the old code always read — is untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: [TWI_223 SOR_095]
WithP2Hand: [SOR_128]
WithP3Hand: [SOR_171 SEC_080]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:p3Hand-0

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P3HANDCOUNT:1
P3DISCARDCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
