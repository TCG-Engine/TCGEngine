# TS26_026 Mother Talzin — When Defeated DECLINE branch: P1 declines the discard, so no card leaves P2's
# hand and P2 does NOT draw. Talzin still died to LAW_124's counter.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_026:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P2HANDCOUNT:1
