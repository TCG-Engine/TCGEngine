# CR 6.1 Empty Deck — "If a player would draw a card from their empty deck, they instead deal 3 damage
# to their base for each card they would draw." P1 has an empty deck; on reaching the regroup phase it
# would draw 2, so instead it takes 2 × 3 = 6 damage to its base.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP2Deck: SOR_046 SOR_046 SOR_046

## WHEN
- P1>Pass

## EXPECT
P1BASEDMG:6
