# SHD_091 Jabba's Rancor — "If you control Jabba the Hutt (as a leader or unit), this unit costs 1 less."
# With Jabba (SHD_006) as P1's leader, the 8-cost Rancor costs 7 → 1 resource left of 8. Played with no
# other units, the When Played damage has no valid targets and fizzles (no decision).

## GIVEN
CommonSetup: grk/grk/{myLeader:SHD_006;myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_091
P1NODECISION
