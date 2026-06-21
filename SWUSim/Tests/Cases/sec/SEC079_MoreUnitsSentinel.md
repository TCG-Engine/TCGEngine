# SEC_079 Corrupt Politician (Ground, 2/2) — "While you control more units than an opponent, this unit
#   gains Sentinel." P1 controls 2 units vs P2's 0 → SEC_079 has Sentinel.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_079:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
