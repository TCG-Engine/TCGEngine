# IBH_023 General Rieekan — with no OTHER Heroism unit, the action still exhausts Rieekan but no attack
#   happens (a non-Heroism unit is not eligible).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IBH_023:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1NODECISION
