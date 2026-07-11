# SHD_044 Razor Crest — with only a non-upgrade (SOR_095 unit) in the discard pile, the "return an upgrade"
# offer has no valid target and fizzles cleanly (no decision, discard untouched).

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION
