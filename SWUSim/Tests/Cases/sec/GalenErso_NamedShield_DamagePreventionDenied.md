# SEC_046 Galen Erso — naming "Shield" denies the Shield token's damage-prevention ability.
# P1 plays Galen and names "Shield". P1 then attacks P2's shielded SOR_063 (2/4) with SOR_095 (3 power).
# Normally the shield would absorb the hit; with the Shield token's ability denied, SOR_063 takes the
# full 3 damage AND the shield token stays attached (it wasn't consumed — it just did nothing).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
