# DrawSelfShield
#// LAW_052 The Mandalorian (6/5) — When Played: Draw a card. + "When you draw 1+ cards during the action
#// phase: Give a Shield token to this unit." His own When-Played draw (in the action phase) self-shields him.

## GIVEN
CommonSetup: brw/bgw/{myResources:6}
WithP1Deck: SOR_237
WithP1Hand: LAW_052

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_052
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:0
