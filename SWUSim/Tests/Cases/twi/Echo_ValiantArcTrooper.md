# Coordinate_Active_PlusTwoTwo
#// TWI_090 Echo (Unit 2/2, Ground) — "Coordinate - This unit gets +2/+2." With 3 friendly units
#// (Coordinate active) Echo is 4/4; with only 2 (inactive) it stays 2/2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_090:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# Coordinate_Inactive_NoBonus
#// TWI_090 Echo — with only 2 friendly units (Coordinate inactive), no +2/+2: stays 2/2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_090:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
