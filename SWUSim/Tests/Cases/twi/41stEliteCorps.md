# Coordinate_Active_PlusHP
#// TWI_045 41st Elite Corps (Unit 3/3, Ground, cost 3) — "Coordinate - This unit gets +0/+3." With 3
#// friendly units in play (Coordinate active), the unit's HP is 3+3 = 6; power stays 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_045:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:POWER:3

---

# Coordinate_Inactive_NoBonus
#// TWI_045 41st Elite Corps — with only 2 friendly units (Coordinate INACTIVE), no +0/+3: HP stays 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_045:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:POWER:3
