# TWI_147 Anakin Skywalker — with Coordinate inactive (Anakin is the only friendly unit), attacking
# does NOT draw: deck stays 2.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_147:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1DECKCOUNT:2
