# Conditional
#// LOF_261 Constructed Lightsaber — Attach to a Force unit. If attached unit is a Heroism unit it gains
#// Restore 2; if a Villainy unit it gains Raid 2. Plo Koon (Force/Heroism) gets Restore; SOR_038
#// (Force/Villainy) gets Raid.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_038:1:0
WithP1GroundArenaUpgrade: 0:LOF_261
WithP1GroundArenaUpgrade: 1:LOF_261

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
