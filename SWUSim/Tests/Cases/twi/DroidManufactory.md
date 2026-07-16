# DeployMakesDroids
#// TWI_022 Droid Manufactory (Base, 24 HP) — "When you deploy a leader: Create 2 Battle Droid tokens."
#// Deploying Luke creates 2 Battle Droids (TWI_T01) alongside the deployed leader unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myBase:TWI_022}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:2:CARDID:SOR_005
