# SEC_139 Miraj Scintel — "While a friendly unit is attacking a damaged unit, the attacker gains
#   Overwhelm." With SEC_139 in play, SOR_095 (3 power) attacks the damaged SOR_046 (1 remaining HP) →
#   kills it, 2 excess overflows to P2's base.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_139:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION
