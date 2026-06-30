# LAW_057 Benthic "Two Tubes" (3/2) — When Defeated: deal 1 damage to a base. Benthic attacks SOR_046
# (3/7) and dies to the counter (decline the OnAttack deal); its When Defeated deals 1 to P2's base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1
