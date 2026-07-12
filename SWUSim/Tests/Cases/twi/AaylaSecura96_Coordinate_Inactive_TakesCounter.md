# TWI_096 Aayla Secura — with Coordinate INACTIVE (she's the only friendly unit), the On Attack
# prevention does not apply: attacking SOR_046 (3/7) she takes the 3 counter damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_096:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:6
