# Upgraded_GainsOverwhelm
#// SHD_169 Clan Challengers (5-cost 3/6 ground) — Raid 3 + "While this unit is upgraded, it gains
#// Overwhelm." Regression guard for the hand-maintained conditional-keyword switch: with an upgrade attached
#// it has Overwhelm; without one it does not.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_169:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SHD_169:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
