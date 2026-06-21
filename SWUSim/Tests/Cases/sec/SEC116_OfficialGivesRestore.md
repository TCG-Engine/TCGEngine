# SEC_116 Nubian Star Skiff (Space, 4/4) — "While you control an Official unit, this unit gains Restore
#   2." With SEC_041 (Official) in play, SEC_116 has Restore.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_116:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
