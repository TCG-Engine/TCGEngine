# TWI_013 Mace Windu (Leader, deployed) — "When Deployed: Deal 2 damage to each damaged enemy unit."
# Deploying hits the damaged SOR_046 (2 → 4) but not the undamaged SOR_128.
## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myLeader:TWI_013}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:2 SOR_128:1:0]
## WHEN
- P1>DeployLeader
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:DAMAGE:0
