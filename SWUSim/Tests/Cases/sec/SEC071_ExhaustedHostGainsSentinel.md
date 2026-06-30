# SEC_071 (upgrade, +1/+3) — "While attached unit is exhausted, it gains Sentinel." The host SEC_041 is
#   exhausted → it has Sentinel.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:0:0
WithP1GroundArenaUpgrade: 0:SEC_071

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
