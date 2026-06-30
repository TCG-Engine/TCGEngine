# SOR_115 Agent Kallus — "Use this ability only once each round." Two enemy UNIQUE units are defeated
# in the same round; Kallus draws only for the FIRST. Kallus (4/4) kills SOR_079 (1/4) → draw (YES);
# then LAW_124 (4/7) kills SOR_109 (2/3) → no second offer. P1 drew exactly 1 (deck 2 → 1).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_109:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:1
