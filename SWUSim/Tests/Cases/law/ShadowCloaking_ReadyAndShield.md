# LAW_043 Shadow Cloaking (Vigilance,Aggression,Villainy event, cost 5) — "Ready a unit and give a
# Shield token to it." One exhausted friendly unit on board -> single target auto-resolves: it readies
# and gains a Shield. (rrk covers Aggression+Villainy; Vigilance pip is off-aspect +2 -> budget 7.)

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
