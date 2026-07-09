# SHD_174 Hotshot DL-44 Blaster (+2/+0 upgrade, "Attach to a non-VEHICLE unit", Smuggle 3
# [Cunning]) — "When played using Smuggle: Attack with attached unit." Smuggled from resources onto
# the ready marine (single valid host → auto), which then attacks: base takes 3+2 = 5. The spent
# slot is replaced from the deck.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:0
P1DECKCOUNT:0
