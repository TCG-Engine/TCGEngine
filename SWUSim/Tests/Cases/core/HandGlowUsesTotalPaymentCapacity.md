# Credits_MakeAnOtherwiseUnaffordableCardGlow
#// Hand affordability glow (live report, 2026-08-18): "affordable cards in hand when have Credits.
#// can still click but it's not highlighted."
#//
#// SelectionMetadata() returns `highlight:false` for a hand card CanAffordActivationReserve() says you
#// cannot pay for, and that function counted READY REAL RESOURCES ONLY — it explicitly skipped Credit
#// tokens and never consulted SEC_122. But the play itself routes through SWUOfferAltPayment, which
#// accepts both, so the card WAS playable: the player saw a dark card, clicked it anyway, and it worked.
#// The glow must be gated on SWUTotalPaymentCapacity — ready resources + usable Credits + (with SEC_122)
#// ready Droids — the same number every offer in the engine is gated on.
#//
#// Here: cost 2 with ONE ready resource and one Credit. Capacity 2, so it glows.
#// COVERAGE: offer=N/A (this is the passive hand glow, not a target offer) ·
#//           decline=N/A (nothing optional) ·
#//           boundary=NoCredits_StillDark (capacity 1 vs 2 — the N-1 partner of this section) ·
#//           control=N/A (the glow is computed for the TURN PLAYER over their own hand; a card in
#//                   someone else's hand is never highlighted, asserted by the owner check above it) ·
#//           reqboundary=N/A (SelectionMetadata is recomputed per render; no state crosses a decision)

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## EXPECT
P1HANDGLOW:0

---

# NoCredits_StillDark
#// The boundary partner. Identical board with the Credit removed: capacity is 1 against a cost of 2, so
#// the card must NOT glow. Without this, a fix that simply always returns true would pass the section
#// above and nothing would notice.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095

## EXPECT
P1HANDGLOWNOT:0

---

# CreditsAlone_CoverTheWholeCost
#// Zero real resources and two Credits against a cost of 2. Capacity comes ENTIRELY from Credits, which
#// is the case a "ready resources, plus a bit" patch would still get wrong.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 2

## EXPECT
P1HANDGLOW:0

---

# CreditsShortOfTheCost_StillDark
#// The other side of the same boundary: one Credit and no resources against a cost of 2. Capacity 1 < 2,
#// so still dark. Pairs with CreditsAlone_CoverTheWholeCost as an exact N vs N-1 on the Credit axis.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## EXPECT
P1HANDGLOWNOT:0

---

# BlankedCredits_DoNotCountTowardTheGlow
#// LAW_117 Conveyex Security Captain blanks ENEMY Credit tokens, and a blanked Credit cannot be defeated
#// to pay 1 less — SWUUsableCreditTokenMzIDs returns none, so SWUTotalPaymentCapacity drops back to the
#// real resources. The glow has to follow. This is the section that proves the fix went through the
#// shared capacity helper rather than bolting a raw Credit COUNT onto the old ready-resource tally:
#// a count-based fix lights the card up and the payment offer never appears.
#// P2 controls the Conveyex; P1 holds the Credit.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP2GroundArena: LAW_117:1:0

## EXPECT
P1HANDGLOWNOT:0

---

# Droids_CountTowardTheGlow_WithSEC122
#// The third tier of the same payment chain: SEC_122 Vuutun Palaa lets ready friendly Droids pay costs,
#// so they count toward capacity too. One ready resource + a ready Droid covers a cost of 2.
#// ⚠ SEC_122 is a SPACE Capital Ship (Separatist/Vehicle/Capital Ship) and is NOT itself a Droid, so it
#// enables the tier without being a payer — the fixture needs a separate real Droid, here SOR_059 2-1B
#// Surgical Droid. Putting SEC_122 alone on the board makes this section silently unwinnable.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: SOR_059:1:0

## EXPECT
P1HANDGLOW:0

---

# Droids_WithoutSEC122_DoNotCount
#// The boundary partner for the Droid tier: the same ready Droid with NO SEC_122 in play pays nothing,
#// so capacity is back to 1 and the card stays dark.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_059:1:0

## EXPECT
P1HANDGLOWNOT:0
