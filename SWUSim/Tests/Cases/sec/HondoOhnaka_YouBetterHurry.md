# GrantsRaidToOthers
#// SEC_140 Hondo Ohnaka (Ground, 6/5) — "Each other friendly unit gains Raid 1." The friendly SEC_041
#//   gains Raid; SEC_140 itself (no innate Raid) does not.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_140:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
