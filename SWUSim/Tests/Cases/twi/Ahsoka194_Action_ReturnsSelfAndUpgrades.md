# TWI_194 Ahsoka Tano — "Action [2 resources]: Return this unit and each upgrade on her to their
# owners' hands." Ahsoka has a SOR_120 (+2/+2) attached. Using the action (paying 2 resources) returns
# both Ahsoka and the upgrade to P1's hand: ground empties, hand gains 2, resources spent.

## GIVEN
CommonSetup: yyw/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_194:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1RESAVAILABLE:0
