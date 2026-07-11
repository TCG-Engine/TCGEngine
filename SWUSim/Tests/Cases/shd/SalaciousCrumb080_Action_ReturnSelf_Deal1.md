# SHD_080 Salacious Crumb — "Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to a
# ground unit." Using the action returns Crumb to P1's hand and deals 1 to the enemy SOR_046.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
