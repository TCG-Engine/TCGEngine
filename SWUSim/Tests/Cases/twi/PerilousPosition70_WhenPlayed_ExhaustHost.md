# TWI_070 Perilous Position (Upgrade -2/-2, cost 3, Vigilance, Condition) — "When Played: Exhaust attached
# unit." Played on SOR_046 (3/7), it exhausts the host and reduces it to 1/5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TWI_070}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:1
