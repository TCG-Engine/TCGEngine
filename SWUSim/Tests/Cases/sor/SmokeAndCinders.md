# BothPlayersDiscardToTwo
#// SOR_174 Smoke and Cinders (event, cost 5) — "Each player discards all but 2 cards (of their choice)
#// from their hand." Both players hold 3 cards (after P1 plays Smoke and Cinders), each keeps 2 of their
#// choice and discards the third. Aggression off-aspect for SOR_009 (Command) → WithP1Resources:7.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# SmallHand_NoDiscard
#// SOR_174 Smoke and Cinders — a player holding 2 or fewer cards discards nothing (and gets no
#// decision). P1 (3 cards after playing) keeps 2/discards 1; P2 (1 card) keeps it, no decision.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1DISCARDCOUNT:2
