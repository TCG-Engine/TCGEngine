# TWI_007 Captain Rex (Leader, deployed) — "When Deployed: Create a Clone Trooper token. Each other
# friendly Trooper unit gets +0/+1." Deploying Rex creates a Clone and buffs SOR_095 (Trooper) to 3/4.
## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:TWI_007}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:2:CARDID:TWI_T02
