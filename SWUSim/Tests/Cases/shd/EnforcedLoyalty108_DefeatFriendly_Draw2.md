# SHD_108 Enforced Loyalty (2-cost event, Command/Command) — "Defeat a friendly unit. If you do, draw 2
# cards." With one friendly unit (SEC_080) it auto-resolves: SEC_080 is defeated (to P1's discard, joining
# the event = 2) and P1 draws 2 from its deck.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:0
P1DISCARDCOUNT:2
