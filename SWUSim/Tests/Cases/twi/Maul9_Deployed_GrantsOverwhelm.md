# TWI_009 Maul (Leader, deployed) — Overwhelm + "Each other friendly unit gains Overwhelm." Deploying Maul
# grants SOR_095 Overwhelm.
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;myLeader:TWI_009}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
