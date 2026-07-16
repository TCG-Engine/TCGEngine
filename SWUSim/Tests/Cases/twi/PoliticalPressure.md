# OpponentDeclines_CreatesDroids
#// TWI_222 Political Pressure (Event, cost 1, Cunning) — "Choose an opponent. They may discard a
#// random card from their hand. If they don't, create 2 Battle Droid tokens." The opponent DECLINES
#// (AnswerDecision:NO) → the caster creates 2 Battle Droid tokens; opponent's hand is untouched.
#// Driven with WithActivePlayer:1 (not P1OnlyActions) so P2 can answer the cross-player YESNO.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222;theirhandCardIds:SOR_095}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2HANDCOUNT:1

---

# OpponentDiscards_NoDroids
#// TWI_222 Political Pressure — the opponent ACCEPTS (AnswerDecision:YES) and discards a random card
#// from their (1-card) hand → no Battle Droids are created. With exactly 1 card in hand the "random"
#// discard is deterministic.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222;theirhandCardIds:SOR_095}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
