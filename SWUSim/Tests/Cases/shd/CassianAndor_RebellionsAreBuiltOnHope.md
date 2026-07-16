# SmugglePlay_EntersReady
#// SHD_148 Cassian Andor (3-cost 3/5) — "When played using Smuggle: Ready this unit." Smuggled from
#// resources (cost 5, Aggression+Heroism covered by rw leader): he enters play READY instead of the
#// normal exhausted entry.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1Resources: 5:SOR_046:1,1:SHD_148:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:5

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_148
P1GROUNDARENAUNIT:0:READY
P1RESCOUNT:6
P1DECKCOUNT:0
