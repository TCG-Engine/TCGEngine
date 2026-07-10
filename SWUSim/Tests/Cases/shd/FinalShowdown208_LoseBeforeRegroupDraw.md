# SHD_208 Final Showdown — the lose-check happens at the START of the regroup phase, BEFORE the draw
# step. Edge case: P2 has an empty deck and a base at 5 HP remaining (25 damage on a 30-HP base). P1
# plays Final Showdown and passes. Because P1 loses at regroup start (before the draw), the game is
# already over when P2 would draw — so the CR 6.1 deck-out damage (which would deal P2 6 and defeat its
# 5-HP base) never applies: P2 wins with 5 HP left (P2's base damage is unchanged at 25). (P2's deck is
# empty by default — no P2Deck directive.) This is the ordering guard: DoDrawCard no-ops once a winner
# is set, so the loss declared at RegroupPhaseStart preempts the regroup draw's deck-out damage.

## GIVEN
CommonSetup: yyk/yyk/{theirBaseDamage:25}
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_208

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P2WIN
P2BASEDMG:25
