# Imprisoned_SuppressesAbilities
#// SHD_072 Imprisoned — "Attach to a non-leader unit. Attached unit loses its current abilities and
#// can't gain abilities." A Sentinel unit (SOR_049) wearing Imprisoned loses Sentinel; an identical
#// unit without it keeps Sentinel.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArena: SOR_049:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
