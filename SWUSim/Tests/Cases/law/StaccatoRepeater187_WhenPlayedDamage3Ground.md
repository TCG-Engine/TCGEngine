# LAW_187 "Staccato Lightning" Repeater (Upgrade, +3/+1) — "When Played: Deal 1 damage to each of up to
# 3 different ground units." Played onto P1's SEC_080; P1 deals 1 to each of P2's three SOR_128 (3/1),
# defeating all three.

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_187
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0
