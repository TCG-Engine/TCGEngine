# JTL_174 Hotshot Maneuver — "Choose a friendly unit. For each of its 'On Attack' abilities, deal 2
# damage to a different enemy unit. Then, attack with the chosen unit." The chosen unit JTL_249
# (Millennium Falcon, 3 power) has NO On Attack ability, so no damage is dealt; it just attacks the
# P2 base for 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1NODECISION
