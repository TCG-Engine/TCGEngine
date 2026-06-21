# SEC_116 — without an Official unit in play, SEC_116 does NOT have Restore.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_116:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Restore
