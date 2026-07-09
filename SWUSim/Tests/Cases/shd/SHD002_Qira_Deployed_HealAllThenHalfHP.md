# SHD_002 Qi'ra (deployed) — "When Deployed: Heal all damage from each unit. Then, deal damage to each
# unit equal to half its remaining HP, rounded down." Deployed (5 resources): SOR_046 (7 HP, 4 damage)
# heals to 0 then takes floor(7/2)=3; the enemy Wampa (SOR_164, 5 HP) takes floor(5/2)=2.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_002;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:4
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:2
