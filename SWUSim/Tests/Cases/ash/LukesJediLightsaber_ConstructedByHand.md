# LukeGainsSentinel
#// ASH_066 Luke's Jedi Lightsaber (Upgrade, non-Vehicle) — "If attached unit is Luke Skywalker, he gains
#// Sentinel." Attached to SOR_051 (Luke Skywalker) it grants Sentinel; attached to SOR_095 (not Luke) it
#// does not.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: SOR_051:1:0
WithP1GroundArenaUpgrade: 0:ASH_066
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:ASH_066
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_051
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
