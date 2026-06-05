# JTL_032 Director Krennic — "the FIRST unit you play each round with a When Defeated ability costs 1
# less" is a PER-PLAYER, per-round slot consumed by ANY such play. P1 plays JTL_033 (a When-Defeated
# unit) BEFORE Krennic is in play (so no discount), then plays Krennic, then plays a second JTL_033.
# Because the first When-Defeated unit already consumed the round's slot, the second is NOT discounted —
# all three cards are paid at full cost (2 + 2 + 2 = 6), leaving 0 resources.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_033 JTL_032 JTL_033

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
