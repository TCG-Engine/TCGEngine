# SEC_046 Galen Erso — naming a unit denies its "When this unit deals combat damage to a base" trigger.
# P1 names "Chopper" (SEC_147, "...deals combat damage to a base: Each player discards a card"). P2's
# Chopper (4/1) attacks P1's base for 4, but the discard trigger does NOT fire — both players keep their
# hand cards.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1Hand: SOR_095
WithP2GroundArena: SEC_147:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Chopper
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1HANDCOUNT:1
P2HANDCOUNT:1
