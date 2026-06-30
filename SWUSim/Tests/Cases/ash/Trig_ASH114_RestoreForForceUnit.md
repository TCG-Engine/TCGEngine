# ASH_114 Sabine's Lightsaber (Upgrade, non-Vehicle) — "If attached unit is Sabine Wren or a Force unit,
# it gains Restore 2." Attached to ASH_112 (a Force unit) it grants Restore; attached to SOR_095 (neither
# Sabine nor Force) it does not.
## GIVEN
CommonSetup: ggw/ggk
WithP1GroundArena: ASH_112:1:0
WithP1GroundArenaUpgrade: 0:ASH_114
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:ASH_114
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_112
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
