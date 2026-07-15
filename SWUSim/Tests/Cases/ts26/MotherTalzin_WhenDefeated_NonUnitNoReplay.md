# TS26_026 Mother Talzin — When Defeated, NON-UNIT discarded: the replay clause is unit-only, so a
# discarded event (SOR_235) is NOT flagged playable. P1 discards it (P2 draws), then the attempted
# PlayFromOpponentDiscard no-ops (no OTPN modifier) and the event stays in P2's discard.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_235;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_026:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirHand-0
- P1>PlayFromOpponentDiscard:0
## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2HANDCOUNT:1
