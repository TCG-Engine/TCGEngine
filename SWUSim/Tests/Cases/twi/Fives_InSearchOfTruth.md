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

---

# DeclinedEventClause_StillTriggersFives
#// TWI_216 Fives — "When you play an EVENT". The trigger is on PLAYING the event, not on the event doing
#// anything, so DECLINING one of the event's own optional clauses must not cancel it.
#// P1 plays LOF_225 Three Lessons and declines its "you may play a unit from your hand" (answer PASS).
#// The event is still played, so Fives fires: P1 puts the Clone TWI_109 from the discard on the bottom of
#// the deck and draws 1. The discard count is the sharp assertion — 1 (only the spent event) when Fives
#// resolved, 2 (the event AND the un-recycled Clone) when the observer was skipped.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10;handCardIds:LOF_225,SOR_237;discardCardIds:TWI_109}
P1OnlyActions: true
WithP1GroundArena: TWI_216:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:3
P1DISCARDCOUNT:1
