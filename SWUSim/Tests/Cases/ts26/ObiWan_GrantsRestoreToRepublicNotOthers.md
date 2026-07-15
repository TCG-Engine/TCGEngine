# TS26_040 Obi-Wan Kenobi (Unit 4/4, cost 3) — passive: OTHER friendly Republic units gain Restore 1.
# The friendly Republic Clone (TWI_T02) has Restore; the non-Republic SEC_080 does not; and Obi-Wan
# himself does not (the grant is to OTHER units).
## GIVEN
CommonSetup: byw/rrk
WithP1GroundArena: [TS26_040:1:0 TWI_T02:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore
P1GROUNDARENAUNIT:2:NOTKEYWORD:Restore
