# SHD_125 Price on Your Head — attached unit gains "Bounty — Put the top card of your deck into
# play as a resource." P2's marine wears it; LAW_124 defeats the marine; P1 collects: top card of
# P1's deck becomes an EXHAUSTED resource (no "and ready it" wording).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_125
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DECKCOUNT:0
