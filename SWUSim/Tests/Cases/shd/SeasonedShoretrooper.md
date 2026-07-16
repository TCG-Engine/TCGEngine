# FiveResources_NoBuff
#// SHD_083 Seasoned Shoretrooper — with only 5 resources the +2 does not apply (stays 2/3).

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_083:1:0
WithP1Resources: 5

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# SixResources_Buff
#// SHD_083 Seasoned Shoretrooper (2-cost 2/3 ground) — "While you control 6 or more resources, this unit gets
#// +2/+0." With 6 resources it is 4/3.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_083:1:0
WithP1Resources: 6

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
