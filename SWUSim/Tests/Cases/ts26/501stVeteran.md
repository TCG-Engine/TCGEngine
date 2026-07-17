# SentinelWhileUndamagedAndGrit
#// TS26_20 501st Veteran (Unit 0/4, cost 2) — Grit + Raid 1 + "While undamaged it gains Sentinel." The
#// undamaged copy has Sentinel and 0 power; the 1-damage copy loses Sentinel and gains +1/+1 from Grit
#// (power 1).
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: [TS26_20:1:0 TS26_20:1:1]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:POWER:1
