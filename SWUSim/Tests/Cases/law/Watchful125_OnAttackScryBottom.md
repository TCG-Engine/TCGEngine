# LAW_125 Watchful (Upgrade) — grants "On Attack: Look at the top card of a deck. You may put it on the
# bottom of that deck." SEC_080 wears Watchful and attacks the base. Only P1 has a deck here, so the
# deck-choice step is skipped (auto-picks P1's deck); P1 looks at the top (SOR_046) and bottoms it, so
# the new top is SOR_095.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Bottom

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
