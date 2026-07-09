# SHD_046 Rey (base cost 5, Heroism/Vigilance) — "While playing this unit, ignore her Heroism aspect
# penalty if you control Kylo Ren." P1's leader is Kylo Ren (SHD_011) and its base covers Vigilance but not
# Heroism; with Kylo Ren the Heroism pip is waived, so Rey costs the printed 5 (7 resources → 2 left).

## GIVEN
CommonSetup: brk/rrk/{myLeader:SHD_011;myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_046
P1RESAVAILABLE:2
