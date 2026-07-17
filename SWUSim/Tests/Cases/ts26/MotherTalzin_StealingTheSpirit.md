# WhenDefeated_Decline
#// TS26_26 Mother Talzin — When Defeated DECLINE branch: P1 declines the discard, so no card leaves P2's
#// hand and P2 does NOT draw. Talzin still died to LAW_124's counter.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_26:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P2HANDCOUNT:1

---

# WhenDefeated_DiscardUnitReplay
#// TS26_26 Mother Talzin (Unit 5/4, cost 5, Sentinel) — When Defeated: look at an opponent's hand and
#// discard a card from it; if you do, they draw. If the discarded card is a unit, this phase you may play
#// it from their discard, ignoring aspect penalties. Talzin attacks LAW_124 (4/7) and dies to the 4 counter;
#// P1 discards P2's only card (SOR_095, a unit), P2 draws, then P1 replays SOR_095 from P2's discard.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_26:1:0
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

---

# WhenDefeated_NonUnitNoReplay
#// TS26_26 Mother Talzin — When Defeated, NON-UNIT discarded: the replay clause is unit-only, so a
#// discarded event (SOR_235) is NOT flagged playable. P1 discards it (P2 draws), then the attempted
#// PlayFromOpponentDiscard no-ops (no OTPN modifier) and the event stays in P2's discard.
## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_235;myResources:5}
P1OnlyActions: true
WithP1GroundArena: TS26_26:1:0
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
