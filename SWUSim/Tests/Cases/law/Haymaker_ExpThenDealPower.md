# LAW_168 Haymaker (Command event, cost 4) — "Give an Experience token to a friendly unit. That unit
# deals damage equal to its power to an enemy unit in the same arena." SOR_095 (3/3) gets Exp -> 4/4,
# then deals 4 to the lone enemy ground unit SOR_046 (3/7, survives at DAMAGE:4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_168

## WHEN
# One friendly unit -> Exp target auto-resolves; one enemy ground unit -> deal target auto-resolves.
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
