# SHD_198 passive is once per round ("the first Clone unit you play each round"). P1 controls SHD_198
# and plays TWO off-aspect Clone units (SOR_160, Aggression, cost 2 each). The first is waived (cost 2);
# the second pays the +2 penalty (cost 4). Total spent = 6, so 6 resources are exactly consumed — if the
# charge weren't consumed, the second would also be waived and 2 would remain.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_198:1:0
WithP1Hand: SOR_160
WithP1Hand: SOR_160
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0
