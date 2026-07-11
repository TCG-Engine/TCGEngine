# CR 6.1 Empty Deck — the rule applies to ANY draw, not just the regroup draw. With an empty deck, P1
# plays SOR_175 ("Draw 2 cards…"); unable to draw either card, P1 instead takes 2 × 3 = 6 damage to its
# base. (The "discards 2" rider is inert — P1 has damaged no base this phase.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_175

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:6
