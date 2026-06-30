# ASH_045 Reanimated Night Trooper (Ground, 2/2) — When Defeated: look at the top card of a deck; you may
# discard it. The Trooper attacks SOR_046 and dies; it looks at the opponent's deck top and discards it
# (P2 deck 2 → 1, discard 0 → 1).
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:1
