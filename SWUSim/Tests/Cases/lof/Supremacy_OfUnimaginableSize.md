# BuffVehicles
#// LOF_089 Supremacy (12/12) — Ambush + "Other friendly Vehicle units get +6/+6." The friendly X-Wing
#// (2/3 Vehicle) becomes 8/9; Supremacy itself (a Vehicle) is unaffected by its own aura.

## GIVEN
CommonSetup: ggw/rrk
WithP1SpaceArena: LOF_089:1:0
WithP1SpaceArena: SOR_237:1:0

## EXPECT
P1SPACEARENAUNIT:1:POWER:8
P1SPACEARENAUNIT:1:HP:9
P1SPACEARENAUNIT:0:POWER:12
