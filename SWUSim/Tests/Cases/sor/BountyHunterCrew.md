# ReturnEventEnemyDiscard
#// SOR_183 Bounty Hunter Crew — the event returns to its OWNER's hand, even from the OPPONENT's
#// discard. P1 plays it and returns Open Fire from P2's discard → it lands in P2's hand (not P1's).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;theirDiscardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:0

---

# ReturnEventOwnDiscard
#// SOR_183 Bounty Hunter Crew — "When Played: You may return an event from a discard pile to its
#// owner's hand." P1 plays it (Ambush + WhenPlayed both fire); the WhenPlayed returns Open Fire from
#// P1's OWN discard to P1's hand. (P2 has no units so Ambush has no target.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0

---

# WithEnemyUnit_ReturnThenDeclineAmbush
#// SOR_183 Bounty Hunter Crew — played WITH an enemy unit on board, so BOTH entry triggers fire
#// (Ambush + WhenPlayed) → the trigger-order MZCHOOSE appears. Resolving the WhenPlayed first returns
#// Open Fire from P1's discard to hand; the Ambush is then declined (the enemy unit is untouched).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
