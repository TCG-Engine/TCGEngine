# CR 6.1 Empty Deck — the 3-per-card damage applies only to the cards that CAN'T be drawn. P1's deck has
# just 1 card; at the regroup phase it would draw 2, so it draws the 1 real card and takes 3 damage for
# the single card it could not draw (not 6).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Deck: SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046

## WHEN
- P1>Pass

## EXPECT
P1BASEDMG:3
