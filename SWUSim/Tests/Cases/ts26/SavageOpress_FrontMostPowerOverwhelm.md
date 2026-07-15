# TS26_005 Savage Opress (leader front, undeployed) — "Each friendly unit with the most power among
# friendly units gains Overwhelm." SOR_198 (6 power) has the most and gains Overwhelm; SEC_080 (3 power)
# does not.
## GIVEN
CommonSetup: rrk/rrk/{myLeader:TS26_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_198:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
