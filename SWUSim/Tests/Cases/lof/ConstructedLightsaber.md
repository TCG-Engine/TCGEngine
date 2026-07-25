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

---

# NeutralHost_GainsSentinelNotRaidOrRestore
#// LOF_261 Constructed Lightsaber — third branch: "If attached unit is a non-Heroism, non-Villainy unit, it
#// gains Sentinel." Attached to SOR_061 Guardian of the Whills (Vigilance Force — neither Heroism nor
#// Villainy), which gains Sentinel and NOT Raid/Restore. (Regression: this branch was unimplemented — the
#// Villainy→Raid 2 and Heroism→Restore 2 branches existed but a neutral host got no keyword at all.)

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: SOR_061:1:0
WithP1GroundArenaUpgrade: 0:LOF_261

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
