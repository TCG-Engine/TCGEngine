# OnAttackDebuffUpgraded
#// ASH_056 Huyang (Ground, 2/4) — On Attack: you may give an upgraded unit -4/-0 for this phase. Huyang
#// attacks the enemy base; the only upgraded unit is SEC_080 (3/3 + SOR_120 → 5/5), which is given -4/-0
#// (power 1).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_056:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:1
