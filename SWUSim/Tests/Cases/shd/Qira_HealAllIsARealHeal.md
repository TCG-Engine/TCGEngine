# Qira_Deployed_HealAll_FiresWhenHealedReactions
#// SHD_002 Qi'ra (deployed): "When Deployed: Heal all damage from each unit. Then, deal damage to each unit
#// equal to half its remaining HP, rounded down." Found by the 2026-09-11 game-log pass: the heal zeroed
#// Damage with a raw write, skipping OnHealUnit — so no "when healed" reaction fired. LAW_047 Baze Malbus:
#// "When 1 or more damage is healed from this unit: you may deal that much damage to a unit." Baze (4 HP,
#// 3 damage) is healed 3, so his "you may deal 3" offer must appear.
#// (Fixture shape from shd/Qira_IAloneSurvived.md.)

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_002;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:3
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1DECISIONTOOLTIP:Deal_3_damage_to_a_unit
LOGCONTAINS:P1's [[SHD_002|Qi'ra]] healed 3 damage from P1's [[LAW_047|
