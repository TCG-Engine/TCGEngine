# SOR_142 Sabine Wren — the On Attack ping is optional ("you may"): declining deals no extra damage,
# only the 2 combat damage to the defender.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
