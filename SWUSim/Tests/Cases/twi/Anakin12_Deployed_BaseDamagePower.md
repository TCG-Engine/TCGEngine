# TWI_012 Anakin Skywalker (Leader, deployed) — Overwhelm + "This unit gets +1/+0 for every 5 damage on
# your base." With 10 damage on P1's base, deployed Anakin (4 power) is 6.
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;myBaseDamage:10;myLeader:TWI_012}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_012
P1GROUNDARENAUNIT:0:POWER:6
