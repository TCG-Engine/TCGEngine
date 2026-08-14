# DealOneToTwoSpace
#// LAW_183 B-Wing Skirmisher (Aggression,Heroism, cost 4, space) — When Played: deal 1 damage to each of
#// up to 2 space units. Hit both enemy SOR_237s (2/3 each) for 1.
#// COVERAGE: offer=WhenPlayed_OfferIsSpaceUnitsIncludingItself (pending SELECTABLEEXACT: space units
#//           only, self included, ground excluded on both sides) · decline=WhenPlayed_ChooseNothing
#//           ("up to 2" includes zero) · boundary=2/1/0 picks: DealOneToTwoSpace +
#//           WhenPlayed_ChooseOnlyOne + WhenPlayed_ChooseNothing · control=N/A (any space unit is
#//           targetable regardless of controller; both sides exercised in DealOneToTwoSpace vs the
#//           offer section's self-target) · reqboundary=offer section holds the choice PENDING across
#//           the end-state read (serialized-decision path)

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_183

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0&theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:1:DAMAGE:1

---

# WhenPlayed_OfferIsSpaceUnitsIncludingItself
#// Intended: the pool is ALL space units — including the just-played B-Wing itself — and never ground
#// units. With an enemy SOR_237 in space, an enemy SEC_080 and a friendly SOR_095 on the ground, the
#// offer is exactly the B-Wing (mySpaceArena-0) and the enemy X-Wing. The decision is left PENDING so
#// the EXPECT reads the live offer.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_183

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0

---

# WhenPlayed_ChooseOnlyOne
#// Intended: "up to 2" allows exactly one — the chosen X-Wing takes 1 and the second enemy X-Wing is
#// untouched.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_183

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_ChooseNothing
#// Intended: "up to 2" includes zero — declining outright deals no damage to anything, including the
#// B-Wing itself.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_183

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
