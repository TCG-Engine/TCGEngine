# Upgraded_GainsSentinel
#// SHD_034 Supercommando Squad (5-cost 4/4 ground) — Shielded + "While this unit is upgraded, it gains
#// Sentinel." Regression guard: with an upgrade it has Sentinel; without one it does not.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_034:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SHD_034:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
