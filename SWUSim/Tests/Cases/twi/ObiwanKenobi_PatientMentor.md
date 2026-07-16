# Deployed_Sentinel_OnAttackHealDeal
#// TWI_003 Obi-Wan Kenobi (Leader, deployed) — Sentinel + "On Attack: Heal 1 damage from a unit. If you do,
#// deal 1 damage to a different unit." Deployed and attacking, Obi-Wan heals 1 from a damaged friendly and
#// deals 1 to an enemy. He also has Sentinel.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myLeader:TWI_003}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1
