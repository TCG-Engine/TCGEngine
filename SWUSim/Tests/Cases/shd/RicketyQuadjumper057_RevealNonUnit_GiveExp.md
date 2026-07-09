# SHD_057 Rickety Quadjumper (2-cost, Vigilance) — "On Attack: You may reveal the top card of your deck.
# If it's not a unit, give an Experience token to another unit. (Leave it on top.)" Top card is the event
# SOR_251 (not a unit) → the friendly SOR_046 gets an Experience token, and the deck is unchanged (2 cards).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_057:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_251
