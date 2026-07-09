# SHD_046 Rey — without Kylo Ren, the Heroism aspect penalty applies. The base covers Vigilance but not
# Heroism, so Rey costs 5 + 2 = 7 (7 resources → 0 left). Contrast with the Kylo-Ren waiver test.

## GIVEN
CommonSetup: brk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_046
P1RESAVAILABLE:0
