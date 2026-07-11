# SHD_198 passive — "Ignore the aspect penalty on the first Clone unit you play each round." P1
# (Cunning/Heroism) controls SHD_198 and plays an off-aspect Clone unit (SOR_160, Aggression, cost 2).
# Normally the off-aspect play would cost 2 + 2 penalty = 4; the waiver drops it to 2, so exactly 2
# ready resources suffice — the play succeeds and all resources are spent.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_198:1:0
WithP1Hand: SOR_160
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_160
P1RESAVAILABLE:0
