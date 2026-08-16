# BaseDamageEqualsHandSize
#// SHD_159 The Chaos of War (3-cost event) — "Deal damage to each player's base equal to the number of cards
#// in that player's hand." After playing (the event leaves P1's hand), P1 holds 2 cards → P1's base takes 2;
#// P2 holds 3 → P2's base takes 3. The per-player amounts prove each base takes ITS OWN controller's hand size.
#// COVERAGE: offer=N/A (no target — both bases are hit automatically) · decline=N/A (not a "you may") ·
#//           control=BaseDamageEqualsHandSize (each base is metered by ITS OWN controller's hand, 2 vs 3) ·
#//           boundary=BaseDamageEqualsHandSize (2 cards → 2 damage) vs EmptyHandTakesZero_UnitsUntouched
#//           (0 cards → 0 damage, and the "base" restriction holds — no unit is damaged) ·
#//           reqboundary=N/A (hand sizes are read once, after the event leaves hand; no decision between)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: [SHD_159 SOR_095 SOR_128]
WithP2Hand: [SOR_095 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:3

---

# EmptyHandTakesZero_UnitsUntouched
#// SHD_159 — the zero end of the scale, plus the "each player's BASE" restriction. SHD_159 is P1's only
#// hand card, so playing it empties P1's hand and P1's base takes 0 while P2 (3 cards) takes 3.
#// No unit is dealt anything: P1's ground SOR_046 and P2's space SOR_225 both end undamaged.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_159
WithP2Hand: [SOR_095 SOR_128 SOR_046]
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
