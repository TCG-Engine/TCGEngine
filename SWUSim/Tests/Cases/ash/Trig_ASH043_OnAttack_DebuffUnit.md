# ASH_043 Corona Four (Space, 2/3, cost 2) — On Attack: you may give a unit -2/-0 for this phase. Corona
# Four attacks P2's base and gives SEC_135 (4/3) -2/-0, dropping it to 2 power; the base takes 2.
## GIVEN
CommonSetup: byk/byk
WithP1SpaceArena: ASH_043:1:0
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2BASEDMG:2
