# LOF_037 Darth Vader — On Attack: defeat an enemy unit with a Shield token on it. Vader (5 power)
# attacks the base; on attack he defeats the shielded enemy 3/7, then deals 5 to the base.

## GIVEN
CommonSetup: bbk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_037:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
