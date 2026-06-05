# SOR_005 Luke Skywalker — Deployed: OnAttack YES → give Shield to another unit.
# Luke attacks base; OnAttack gives shield to P2's unit (valid "another unit" target).

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EPICUSED
