# SOR_204 Greedo — the 2 damage only triggers if the discarded card is NOT a unit. Here the top of
# P1's deck is a UNIT (SOR_095), so no damage: the 3/7 has only its 3 combat damage.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
