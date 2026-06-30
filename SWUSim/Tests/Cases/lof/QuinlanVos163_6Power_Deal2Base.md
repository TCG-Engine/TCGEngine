# LOF_163 Quinlan Vos (4 power) — On Attack: if this unit has 6 or more power, may deal 2 to an enemy
# base. With Academy Training (+2/+2) he is 6 power, so attacking the base deals 6 combat + 2 ability = 8.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:8
