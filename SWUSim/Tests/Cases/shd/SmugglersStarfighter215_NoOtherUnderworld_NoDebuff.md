# SHD_215 Smuggler's Starfighter — without another Underworld unit the When Played does nothing:
# no decision, enemy untouched.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_215
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P1NODECISION
