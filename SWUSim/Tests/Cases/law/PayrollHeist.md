# GrantOnAttackCredit
#// LAW_169 Payroll Heist (Command event, cost 4) — "For this phase, each friendly unit gains: On Attack:
#// Create a Credit token." After playing it, SOR_095 attacks the base and creates a Credit token.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2BASEDMG:3
