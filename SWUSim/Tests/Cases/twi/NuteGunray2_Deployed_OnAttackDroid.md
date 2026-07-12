# TWI_002 Nute Gunray (Leader, deployed) — "On Attack: Create a Battle Droid token." After deploying, the
# leader unit attacks the base and creates a Battle Droid (TWI_T01).
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;myLeader:TWI_002}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
