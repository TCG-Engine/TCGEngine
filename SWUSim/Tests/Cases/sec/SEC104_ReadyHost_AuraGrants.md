# SEC_104 The Will of the People (upgrade, +2/+2) — Attached unit gains: "While this unit is READY, each
#   other friendly unit gains Overwhelm, Raid 1, and Restore 1." Host SEC_041 is ready → the other
#   friendly SEC_042 gains all three.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore
