# SHD_011 Kylo Ren (deployed passive) — "This unit gets -1/-0 for each card in your hand." Deployed
# (4 resources) with 2 cards in hand: his printed 5 power drops to 3.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_011;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: SOR_095

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
