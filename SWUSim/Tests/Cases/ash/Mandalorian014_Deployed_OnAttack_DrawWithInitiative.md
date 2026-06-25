# ASH_014 The Mandalorian (deployed) — On Attack: if you have the initiative, you may draw a
# card. P1 holds the initiative → may draw → hand 1, deck 0.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014:1:1:1
}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
