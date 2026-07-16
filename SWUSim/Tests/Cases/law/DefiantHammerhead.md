# OnAttackBuffSelfDefeat
#// LAW_062 Defiant Hammerhead (6/6, space) — On Attack: if attacking a unit, you may give this unit
#// +4/+0 for this attack; if you do, defeat this unit after the attack. Attacks SOR_237 (2/3): +4 -> 10
#// power kills it; Hammerhead then self-defeats.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_062:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
