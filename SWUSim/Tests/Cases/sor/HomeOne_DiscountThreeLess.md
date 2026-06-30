# SOR_102 Home One — the played unit costs 3 LESS. A single Heroism unit (SOR_100 Wedge, cost 5,
# Command/Heroism) is in discard. P1 has 10 resources: Home One costs 8 (→ 2 left), then Wedge costs
# 5-3 = 2 (→ 0 left). Wedge enters play and the discard empties. Without the -3, Wedge (cost 5) would
# be unaffordable with only 2 resources and would NOT be played.

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;discardCardIds:SOR_100}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:0
P1RESAVAILABLE:0
