# SHD_113 Privateer Crew (2-cost 2/2) — "When played using Smuggle: Give 3 Experience tokens to
# this unit." Smuggle cost 6 [Command]. P1 smuggles it from resources: enters exhausted with 3
# Experience → 5/5. The spent slot is replaced by the deck top (enters exhausted): 7 resources
# stay 7, all exhausted.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SHD_113:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_113
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:7
P1RESAVAILABLE:0
P1DECKCOUNT:0
