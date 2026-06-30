# LOF_053 Heirloom Lightsaber (+2/+2) — Attach to a non-Vehicle unit. If the attached unit is a Force
# unit, it gains Restore 1. On Plo Koon (Force) he is 8/10 and has Restore.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_053

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
