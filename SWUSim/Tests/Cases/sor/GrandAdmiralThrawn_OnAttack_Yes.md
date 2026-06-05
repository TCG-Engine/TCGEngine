# SOR_016 Grand Admiral Thrawn Deployed — OnAttack YES: reveal own deck top (SOR_095, cost 2), exhaust cost-2 friendly unit.
# Thrawn (index 1 after SOR_095 placed at index 0) attacks P2 base. P1's SOR_095 gets auto-exhausted (only target).

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
