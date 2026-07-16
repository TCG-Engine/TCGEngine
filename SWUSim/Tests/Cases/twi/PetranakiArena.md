# LeaderPlusPower
#// TWI_028 Petranaki Arena (Base, 26 HP) — "Each leader unit you control gets +1/+0." Deploying Luke
#// (SOR_005, 4/7) makes his leader unit 5/7.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myBase:TWI_028}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
