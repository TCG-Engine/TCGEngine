# ASH_209 Ezra Bridger (Ground, 6/6, Support) — On Attack: if this unit is upgraded, you may give a unit
# -3/-0 for this phase. Ezra carries SOR_120; attacking the enemy base, the upgraded On Attack gives the
# enemy SEC_080 (3/3) -3/-0 (power 0).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:0
