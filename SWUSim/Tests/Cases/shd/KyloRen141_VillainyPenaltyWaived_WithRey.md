# SHD_141 Kylo Ren (base cost 6, Villainy/Aggression) — "While playing this unit, ignore his Villainy aspect
# penalty if you control Rey." P1's leader is Rey (SHD_004) and its base covers Aggression but not Villainy;
# with Rey the Villainy pip is waived, so Kylo costs the printed 6 (6 resources → 0 left).

## GIVEN
CommonSetup: rrw/rrw/{myLeader:SHD_004;myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_141

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_141
P1RESAVAILABLE:0
