# SHD_141 Kylo Ren — without Rey, the Villainy aspect penalty applies. The base covers Aggression but not
# Villainy, so Kylo costs 6 + 2 = 8 (8 resources → 0 left). Contrast with the Rey-waiver test.

## GIVEN
CommonSetup: rrw/rrw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_141

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_141
P1RESAVAILABLE:0
