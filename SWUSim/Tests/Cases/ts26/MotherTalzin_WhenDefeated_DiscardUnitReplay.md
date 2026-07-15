# TS26_026 Mother Talzin (Unit 5/4, cost 5, Sentinel) — When Defeated: look at an opponent's hand and
# discard a card from it; if you do, they draw. If the discarded card is a unit, this phase you may play
# it from their discard, ignoring aspect penalties. Talzin attacks LAW_124 (4/7) and dies to the 4 counter;
# P1 discards P2's only card (SOR_095, a unit), P2 draws, then P1 replays SOR_095 from P2's discard.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_026:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirHand-0
- P1>PlayFromOpponentDiscard:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2DISCARDCOUNT:0
P2HANDCOUNT:1
