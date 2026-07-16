# Bounty_Draw2
#// SHD_176 Death Mark — attached unit gains "Bounty — Draw 2 cards." P2's marine wears it; LAW_124
#// defeats the marine; P1 collects and draws both seeded cards.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_176
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:0
