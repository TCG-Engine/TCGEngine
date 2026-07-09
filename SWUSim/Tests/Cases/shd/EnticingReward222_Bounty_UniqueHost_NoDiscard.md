# SHD_222 Enticing Reward on a UNIQUE host (ready Synara San, own bounty silent) — the trailing
# "discard a card" does NOT fire. Draw both non-units, keep both: hand 2, no discard decision left.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2
WithP2GroundArenaUpgrade: 0:SHD_222
WithP1Deck: [SOR_120 SOR_095 SOR_251]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_120,SOR_251

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P1NODECISION
