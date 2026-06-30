# SOR_005 Luke Skywalker — Deployed: OnAttack NO → no shield given.

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EPICUSED
