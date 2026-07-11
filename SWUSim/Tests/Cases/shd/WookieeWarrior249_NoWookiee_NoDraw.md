# SHD_249 Wookiee Warrior — without another Wookiee unit, playing it draws nothing.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_249
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
