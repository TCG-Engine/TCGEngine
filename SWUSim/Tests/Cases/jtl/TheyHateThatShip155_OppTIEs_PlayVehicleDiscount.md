# JTL_155 They Hate That Ship — "An opponent creates 2 TIE Fighter tokens and readies them. Then, play
# a Vehicle unit from your hand. It costs 3 resources less." P2 gets two readied TIE Fighters; P1 then
# plays SOR_237 (Alliance X-Wing, cost 1) from hand at −3 (free).

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_155}
P1OnlyActions: true
WithP1Hand: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:JTL_T01
P2SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:1:READY
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
