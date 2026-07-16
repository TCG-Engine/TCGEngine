# LeaderPlusHP
#// TWI_019 Pau City (Base, 26 HP) — "Each leader unit you control gets +0/+1." Deploying Luke (SOR_005,
#// 4/7) makes his leader unit 4/8.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myBase:TWI_019}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:HP:8
