# TS26_014 Yoda — "If you control 7 or more resources, this unit costs 2 resources less to play." With 7
# resources Yoda costs 3 (5 - 2), leaving 4 ready.
## GIVEN
CommonSetup: bgw/rrk/{myResources:7;handCardIds:TS26_014}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:4
