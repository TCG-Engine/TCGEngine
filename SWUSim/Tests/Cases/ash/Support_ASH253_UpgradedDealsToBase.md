# ASH_253 Yellow Aces Bomber (Space, 2/4, Support) — On Attack: if this unit is upgraded, deal 2 damage
# to a base. Carrying SOR_120 (+2/+2 → 4 power), ASH_253 attacks the enemy base: the On Attack deals 2 to
# the enemy base, then combat deals 4 → 6 total.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:6
