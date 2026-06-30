# ASH_049 Shin Hati — with another friendly non-leader GROUND unit (SOR_095) present, she is NOT the only
# one, so she does not gain Sentinel.
## GIVEN
CommonSetup: brk/brk
WithP1GroundArena: ASH_049:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_049
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
