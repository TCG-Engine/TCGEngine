# TS26_008 Ahsoka Tano (leader front) — When you play an event: you may exhaust this leader; if you do,
# look at the top card of your deck and play it (paying its cost), discard it, or leave it. Playing the
# neutral event Confiscate triggers Ahsoka; exhausting her plays SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_008;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:EXHAUSTED
