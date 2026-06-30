# ASH_243 Darth Vader (Ground, 4/6, Shielded) — While this unit is ready, he gains Sentinel. A ready
# Vader has Sentinel; an exhausted one does not.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_243:1:0
WithP1GroundArena: ASH_243:0:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
