# OppDiscardsAndDraw
#// SHD_244 No Bargain (3-cost event, Villainy) — "Each opponent discards a card from their hand. Draw a
#// card." P2 (hand of exactly 1) auto-discards SOR_095; P1 draws a card.
#// COVERAGE: offer=N/A (the discard pick belongs to the OPPONENT and is a hidden-zone choice; with a
#//           one-card hand it auto-resolves and there is no board-visible pool to assert) ·
#//           decline=N/A (mandatory discard, mandatory draw — no "you may") ·
#//           control=OppDiscardsAndDraw (the two halves split across seats: P2 discards, P1 draws) ·
#//           boundary=OppDiscardsAndDraw (opponent holds a card) vs OppEmptyHand_NoDiscard_StillDraws
#//           (empty hand — the discard half finds nothing, the draw half is unaffected) ·
#//           reqboundary=N/A (an event with no player decision between its two halves)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;theirhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: SHD_244
WithP1Deck: [SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1HANDCOUNT:1

---

# OppEmptyHand_NoDiscard_StillDraws
#// SHD_244 — the discard half simply finds nothing when the opponent's hand is already empty; the draw
#// half is unconditional and still happens. P2 discards nothing (discard stays empty) and P1 still
#// draws SOR_128, so P1 ends holding 1 card with No Bargain itself in the discard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_244
WithP1Deck: [SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_244
