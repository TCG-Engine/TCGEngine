# CR 6.1 Empty Deck — deck-out damage can defeat your base and lose you the game. P1's deck is empty and
# its base (30 HP) already has 27 damage. At the regroup phase P1 would draw 2: the first card's 3 deck-out
# damage brings the base to 30 (defeated), ending the game — so P2 wins. (The second draw's damage is not
# applied; the game is already over.)

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:27}
P1OnlyActions: true
WithP2Deck: SOR_046 SOR_046 SOR_046

## WHEN
- P1>Pass

## EXPECT
P2WIN
