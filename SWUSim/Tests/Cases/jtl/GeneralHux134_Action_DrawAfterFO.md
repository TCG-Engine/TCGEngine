# JTL_134 General Hux — Action [Exhaust]: If you played a First Order card this phase, draw a card. P1
# plays the FO unit JTL_236, then uses Hux's action to draw.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_134:1:0
WithP1Hand: JTL_236
WithP1Resources: 5
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
