# SOR_204 Greedo (3/1) — "When Defeated: You may discard a card from your deck. If it's not a unit,
# deal 2 damage to a ground unit." Greedo attacks a 3/7 and dies; his When Defeated discards an
# EVENT (Open Fire) from the top of P1's deck → deals 2 to the only ground unit (the 3/7, which
# already has 3 from combat → 5).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5
