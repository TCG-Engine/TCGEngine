# SEC_260 Inspector's Shuttle (Space, 1/3, cost 2) — When Played: name a card; for each copy of it in
#   an opponent's hand, give an Experience token to this unit. P2 hand has 2 Battlefield Marines → +2/+2 → 3 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION
