# SEC_046 Galen Erso — naming "Spy" denies the Spy token's Raid 2. A Spy token (SEC_T01) is 0 power with
# Raid 2, so attacking a base normally deals 2; with its Raid ability denied it deals 0. P1 plays Galen
# and names "Spy"; P2's Spy then attacks P1's base for 0.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SEC_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Spy
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
