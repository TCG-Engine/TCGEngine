# TS26_075 Jango Fett — with no enemy having attacked your base this phase, Jango does NOT have Ambush.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_075:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
