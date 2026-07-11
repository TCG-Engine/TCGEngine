# SHD_052 Sugi — no enemy upgrade (own-side upgrades don't count) → no Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
