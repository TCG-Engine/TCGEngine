# ASH_049 Shin Hati (Ground, 6/6) — While she is the only friendly non-leader GROUND unit, she gains
# Sentinel. A friendly SPACE unit (SOR_237) doesn't count, so she still has Sentinel.
## GIVEN
CommonSetup: brk/brk
WithP1GroundArena: ASH_049:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_049
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
