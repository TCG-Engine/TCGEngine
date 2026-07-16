# BaseDamageEqualsHandSize
#// SHD_159 The Chaos of War (3-cost event) — "Deal damage to each player's base equal to the number of cards
#// in that player's hand." After playing (the event leaves P1's hand), P1 holds 2 cards → P1's base takes 2;
#// P2 holds 3 → P2's base takes 3. The per-player amounts prove each base takes ITS OWN controller's hand size.

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
