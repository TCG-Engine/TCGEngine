# SOR_016 Grand Admiral Thrawn Deployed — OnAttack NO: declines ability. Friendly unit stays ready.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:DEPLOYED
