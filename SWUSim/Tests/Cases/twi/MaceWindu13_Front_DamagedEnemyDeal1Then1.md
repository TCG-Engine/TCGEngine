# TWI_013 Mace Windu (Leader, front) — "Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy
# unit. Then, if it has 5 or more damage on it, deal 1 more." SOR_046 starts at 4 damage → deal 1 (5) →
# 5+ → deal 1 more (6).
## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:TWI_013}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:4
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
