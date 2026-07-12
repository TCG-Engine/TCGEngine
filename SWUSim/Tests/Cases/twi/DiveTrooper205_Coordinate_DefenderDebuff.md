# TWI_205 Clone Dive Trooper (Unit 2/1, Ground) — "Coordinate - While this unit is attacking, the
# defender gets -2/-0." With 3 friendly units (Coordinate active), the Dive Trooper attacks an enemy
# Clone token (2/2): it deals 2 (defeating the clone), and the clone's counter power 2-2 = 0 → the Dive
# Trooper takes no counter damage and survives.

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_205:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_205
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
