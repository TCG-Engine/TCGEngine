# SHD_249 Wookiee Warrior (4-cost, Heroism) — Grit + "When Played: If you control another Wookiee unit,
# draw a card." With the friendly Wookiee SHD_048 in play, playing it draws a card.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_249
WithP1GroundArena: SHD_048:1:0
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
