# Tech_GrantsSmuggleToPlainCard
#// SHD_248 Tech — "Each friendly resource gains Smuggle. The gained Smuggle cost is that card's cost plus
#// 2 resources and its aspect icons." A plain card with NO printed Smuggle (SOR_046, cost 4, Vigilance/
#// Heroism — both covered) can be played from resources via the granted Smuggle for 4 + 2 = 6. It enters
#// play (exhausted) and is replaced in resources by the top of the deck (net resource count unchanged).

## GIVEN
CommonSetup: bbw/grw
WithP1GroundArena: SHD_248
WithP1Resources: 1:SOR_046:0,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:7
P1RESAVAILABLE:0
