# SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
# (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
# remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
# Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/bbw/{
  myLeader:SOR_004;
  theirLeader:SOR_004:1:1:1:7;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_077

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED
