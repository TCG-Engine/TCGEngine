# SEC_079 — when you do NOT control more units than the opponent (here 1 each), SEC_079 has no Sentinel.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
