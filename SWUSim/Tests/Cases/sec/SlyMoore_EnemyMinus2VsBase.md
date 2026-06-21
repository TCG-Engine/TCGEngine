# SEC_033 Sly Moore (Ground, 2/6) — When Played: for this phase, each enemy unit gets -2/-0 while
#   attacking a base. P1 plays Sly Moore (marks the enemy SOR_046); P2's SOR_046 then attacks P1's
#   base for 3-2 = 1. (Plot auto.)

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_033

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
