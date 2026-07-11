# SHD_174 smuggled onto an EXHAUSTED host — the granted attack can't happen (only ready units
# attack): the upgrade still attaches, no attack, no stuck action (the game cleanly passes on).

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1NODECISION
