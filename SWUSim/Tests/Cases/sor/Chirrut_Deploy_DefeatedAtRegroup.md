# SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
# (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
# remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
# Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
