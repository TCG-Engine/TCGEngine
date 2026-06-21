# LOF_184 Second Sister — On Attack: may discard 2 cards from your deck. For each Force card discarded,
# ready a resource. The top 2 are both Force units, so P1 readies 2 (previously exhausted) resources.

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 2:SOR_095:0
WithP1GroundArena: LOF_184:1:0
WithP1Deck: LOF_050
WithP1Deck: LOF_193

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2
P1DECKCOUNT:0
