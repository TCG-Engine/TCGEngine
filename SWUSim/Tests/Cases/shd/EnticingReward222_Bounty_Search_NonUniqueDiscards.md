# SHD_222 Enticing Reward — "Bounty — Search the top 10 of your deck for 2 non-unit cards, reveal
# and draw them. Then, if this unit isn't unique, discard a card from your hand." NON-unique host
# (marine) → the discard fires. Deck: upgrade + unit + event; the two non-units are drawn, then one
# is discarded. Net: hand 1, own discard 1, the unit filler to the bottom (deck 1).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_222
WithP1Deck: [SOR_120 SOR_095 SOR_251]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_120,SOR_251
- P1>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DECKCOUNT:1
