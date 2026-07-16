# OnAttackMill
#// LAW_192 Bracca Shipbreaker (4/3) — On Attack: discard a card from your deck. Attacks the base; mills 1.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_192:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
