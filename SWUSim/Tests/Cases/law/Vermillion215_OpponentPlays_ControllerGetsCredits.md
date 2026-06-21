# LAW_215 Vermillion — the cross-player branch. P1 reveals its own deck-top (Battlefield Marine) but
# chooses the OPPONENT (P2) to play it. P2 plays it for free — it enters P2's arena owned by P1 (its deck
# owner), controlled by P2 — and the DIFFERENT player (P1) creates 2 Credits.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:2
P2CREDITCOUNT:0
