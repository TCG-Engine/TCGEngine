# PlayEvent_RecycleClone_Draw
#// TWI_216 Fives (Unit 5/5, Ground, cost 5, Cunning, Republic/Clone/Trooper) — Saboteur + "When you play an
#// event: You may put a Clone unit from your discard pile on the bottom of your deck. If you do, draw a
#// card." Playing TWI_175 (Draw 3) triggers Fives; recycling the Clone TWI_109 from discard draws 1 more.

## GIVEN
CommonSetup: yyk/rrk/{myResources:7;handCardIds:TWI_175;discardCardIds:TWI_109}
P1OnlyActions: true
WithP1GroundArena: TWI_216:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:4
P1DECKCOUNT:2
P1DISCARDCOUNT:1
