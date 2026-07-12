# TWI_154 Mister Bones — the "may" is optional: with an empty hand, declining the deal (AnswerDecision:-)
# leaves the enemy ground unit undamaged; only combat hits the base.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_154:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
