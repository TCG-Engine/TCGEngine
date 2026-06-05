# SOR_138 Force Lightning — the "loses all abilities" half is unconditional, but the "pay resources,
# deal 2 each" half is gated on controlling a FORCE unit. With no Force unit, the enemy SOR_063 loses
# Sentinel but takes NO damage and there is no pay step.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0
