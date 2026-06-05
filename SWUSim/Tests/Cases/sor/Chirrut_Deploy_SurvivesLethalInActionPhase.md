# SOR_004 Chirrut Îmwe — Deployed: "During the action phase, this unit isn't defeated by
# having no remaining HP." Chirrut (3/5) attacks Syndicate Lackeys (5/4); he takes 5 combat
# damage (HP 5 → no remaining HP) but SURVIVES because it is still the action phase.

## GIVEN
P1LeaderBase: SOR_004/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:5
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:3
