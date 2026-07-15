# TS26_013 Darth Sidious (Unit 4/6, cost 6) — Hidden. Each OTHER friendly Separatist unit gets +1/+0.
# The friendly Battle Droid (TS26_T01, Separatist) gets +1 power; the Imperial SEC_080 is unaffected;
# Sidious himself is not buffed (the grant is to OTHER units).
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_013:1:0 TS26_T01:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:2:POWER:3
