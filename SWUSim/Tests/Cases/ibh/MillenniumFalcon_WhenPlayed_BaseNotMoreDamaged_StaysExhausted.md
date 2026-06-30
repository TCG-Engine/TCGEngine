# IBH_031 Millennium Falcon — if your base is NOT more damaged than an enemy base, the unit enters
#   exhausted as normal. Both bases at 0 damage → condition false → Falcon stays exhausted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_031

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:IBH_031
P1SPACEARENAUNIT:0:EXHAUSTED
P1NODECISION
