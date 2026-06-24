# ASH_035 Tatooine Repulsor Train (Ground, 8/7) — On Attack: deal 2 damage to a ground unit for each
# friendly exhausted unit. P1 controls SOR_095 (exhausted) and ASH_035, which exhausts itself by
# attacking → 2 exhausted units → 4 damage. ASH_035 attacks the enemy base; the On Attack deals 4 to the
# enemy SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_035:1:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:8
P2GROUNDARENACOUNT:0
