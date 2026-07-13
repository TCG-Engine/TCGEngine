# TWI_017 "Flipatine" (HEROISM face) provides Cunning+Heroism but NOT Villainy — proving the printed
# all-three aspect list is NOT granted wholesale. A Villainy card (SOR_128 Aggression,Villainy) under a
# Vigilance base pays the FULL penalty on BOTH pips (+4), costing 5 (6→1). If the leader wrongly provided
# Villainy, it would cost 3 (→3).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:6;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:1
