# LAW_075 Interrogation Droid (3/1) — When Played: exhaust an enemy unit. If you do and that unit costs
# 3 or less, its controller discards a card. SEC_080 (cost 2) -> exhausted -> P2 discards (2 cards -> picks).

## GIVEN
CommonSetup: ryk/bgw/{myResources:2}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1
