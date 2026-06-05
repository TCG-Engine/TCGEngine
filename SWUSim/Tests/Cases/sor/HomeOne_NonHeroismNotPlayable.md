# SOR_102 Home One — only a [Heroism] unit can be played from discard. With only a non-Heroism unit
# (SEC_080, Villainy) in discard, the When Played fizzles: nothing is played and the discard is intact.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
