# SEC_203 Tala Durith (Ground, 3/3) — "Each other friendly unit gains Hidden." SEC_041 gains Hidden;
#   SEC_203 itself (no other SEC_203, no innate Hidden) does not.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_203:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden
