# Regroup_ExpToNonVehicle
#// LOF_055 Dume (2/7) — "When the regroup phase starts: give an Experience token to each other friendly
#// non-Vehicle unit." At regroup, the friendly SOR_095 (non-Vehicle) gets an Experience token; the friendly
#// X-Wing (Vehicle) and Dume itself do not.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_055:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
