# LOF_166 Blockade Runner (4 power) — Saboteur + "When this unit deals combat damage to a base: may give
# an Experience token to this unit." It attacks the base (4 damage) and gains an Experience token.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_166:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
