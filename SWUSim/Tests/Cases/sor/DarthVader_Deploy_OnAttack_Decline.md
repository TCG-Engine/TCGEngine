# SOR_010 Darth Vader — Deployed: OnAttack NO → no extra damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EPICUSED
