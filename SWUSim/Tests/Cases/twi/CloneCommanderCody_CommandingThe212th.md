# Coordinate_BuffsOthers
#// TWI_114 Clone Commander Cody (Unit 4/4, Ground) — "Overwhelm. Coordinate - Each other friendly unit
#// gets +1/+1 and gains Overwhelm." With 3 friendly units (Coordinate active), each Clone token (2/2)
#// becomes 3/3 and gains Overwhelm. Cody itself (index 0) does NOT get the +1/+1 from itself.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_114:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:POWER:4

---

# Coordinate_Inactive_NoBuff
#// TWI_114 Clone Commander Cody — with only 2 friendly units (Coordinate inactive), the Clone token
#// stays 2/2 and does NOT gain Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_114:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
