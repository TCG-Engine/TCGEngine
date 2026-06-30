# SOR_191 Vanguard Ace — guard: played as the FIRST card this phase → 0 other cards → no Experience
# tokens. Vanguard stays 1/1 with no subcards.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:1
