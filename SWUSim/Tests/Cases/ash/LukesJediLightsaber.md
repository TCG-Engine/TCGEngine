
---

# NonLukeHost_NoSentinel
#// ASH_066 Luke's Jedi Lightsaber — grants Sentinel only if the attached unit is Luke Skywalker. On the
#// non-Luke SOR_095, no Sentinel is granted.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_066
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
