# TWI_011 Ahsoka Tano (Leader, deployed) — "Coordinate - This unit gets +2/+0." With 2 other units, deploying
# Ahsoka gives 3 units (Coordinate active), so the deployed Ahsoka (3 power) is 5.
## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:TWI_011}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:TWI_011
P1GROUNDARENAUNIT:2:POWER:5
