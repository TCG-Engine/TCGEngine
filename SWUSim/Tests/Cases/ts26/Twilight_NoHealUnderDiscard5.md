# TS26_041 Twilight — with only 4 cards in the discard, the "5 or more" condition fails: no heal, P1's
# base damage stays 3.
## GIVEN
CommonSetup: bgw/rrk/{myResources:3;myBaseDamage:3;handCardIds:TS26_041;discardCardIds:SEC_080,SOR_095,SOR_046,SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:3
