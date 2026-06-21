# LOF_176 Lightsaber Throw — Discard a Lightsaber card from your hand; if you do, deal 4 damage to a ground
# unit and draw a card. P1 discards SOR_053 (a Lightsaber), deals 4 to SOR_046 and draws SOR_059.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_053}
P1OnlyActions: true
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1
