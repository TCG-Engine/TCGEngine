# Heal3OwnBase
#// SHD_252 Smuggler's Aid (1-cost event) — "Heal 3 damage from your base." 5 → 2.
#// COVERAGE: offer=N/A (no target — "your base" is fixed) · decline=N/A (not a "you may") ·
#//           control=N/A (the heal is hard-scoped to the caster's own base; there is no opponent-facing
#//           half and no controller dimension) ·
#//           boundary=Heal3OwnBase (played from HAND for its 1 cost) vs Heal3ViaSmuggle (played from the
#//           RESOURCE zone for its Smuggle cost — same effect down the alternate play path) ·
#//           reqboundary=N/A (an event with a single immediate effect and no player decision)

## GIVEN
CommonSetup: gyw/gyw/{myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SHD_252

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1DISCARDCOUNT:1

---

# Heal3ViaSmuggle
#// SHD_252 — the same heal when the card is played from the RESOURCE zone for its Smuggle cost
#// ([3 resources, Heroism], leader supplies the Heroism). SHD_252 sits at resource index 0 with five
#// other resources; smuggling it heals P1's base 5 → 2, replaces its resource slot with the top card of
#// the deck (so the resource count stays 6) and puts SHD_252 in the discard.

## GIVEN
CommonSetup: gyw/gyw/{myBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 1:SHD_252:1,5:SOR_095:1
WithP1Deck: [SOR_128]

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1BASEDMG:2
P1RESCOUNT:6
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_252
