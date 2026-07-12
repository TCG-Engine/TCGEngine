# TWI_198 Enfys Nest — the On Attack window fires the same bounce. Attacking the enemy base, Enfys
# returns the weaker SOR_095 (power 3 < 5) to P2's hand, then combat deals power 5 to the base.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_198:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2BASEDMG:5
