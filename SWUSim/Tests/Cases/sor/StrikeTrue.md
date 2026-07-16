# FriendlyDealsPowerToEnemy
#// SOR_127 Strike True (Event, cost 3) — "A friendly unit deals damage equal to its
#// power to an enemy unit." P1's only friendly is Consular Security Force (SOR_046,
#// power 3); P2's only unit is Battlefield Marine (SOR_095, 3/3). Both selections
#// auto-resolve (one option each): the dealer's 3 power kills the 3-HP Marine.
#// (The dealer takes no counter-damage and survives.)

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # dealer, power 3
WithP2GroundArena: SOR_095:1:0    # target, 3 HP

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
