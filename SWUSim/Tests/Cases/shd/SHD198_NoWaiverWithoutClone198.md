# SHD_198 passive is gated on controlling SHD_198. Without it in play, the off-aspect Clone (SOR_160,
# Aggression, cost 2) pays the +2 aspect penalty → total 4. With only 2 ready resources the play is
# unaffordable: it stays in hand and no resources are spent.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Hand: SOR_160
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2
