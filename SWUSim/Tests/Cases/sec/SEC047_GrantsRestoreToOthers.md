# SEC_047 Defiant (Space, 4/6) — Restore 1 + "Each other friendly unit gains Restore 1." The friendly
#   SEC_041 (no innate Restore) gains Restore; SEC_047 itself keeps its innate Restore.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_047:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
