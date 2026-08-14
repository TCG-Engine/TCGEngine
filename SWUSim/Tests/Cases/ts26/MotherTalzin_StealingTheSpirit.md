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

---

# ADiscardedUPGRADEIsNotPlayableFromTheirDiscard
#// TS26_26 Mother Talzin — "IF the discarded card is a UNIT, for this phase you may play it from their
#// discard pile." Discarding SOR_166 Infiltrator's Skill (an Upgrade) still mills it and still makes P2
#// draw, but it stays sitting in their discard: nothing reaches P1's board.

## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_166;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_26:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirHand-0
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# ADiscardedEVENTIsNotPlayableFromTheirDiscard
#// TS26_26 Mother Talzin — the same gate for the other non-unit type. Confiscate (SOR_251) is discarded
#// and P2 draws, but an Event is not a unit, so it stays in their discard.

## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_251;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_26:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirHand-0
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0

---

# ThePermissionToPlayItLastsOnlyForThatPhase
#// TS26_26 Mother Talzin — "FOR THIS PHASE you may play it from their discard pile". The discarded unit
#// is left unplayed; once the phase is passed out and the next round's resource step declined, SOR_095 is
#// still in P2's discard and can no longer be taken.

## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095;myResources:5}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1GroundArena: TS26_26:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirHand-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0

---

# DefeatedAfterAControlChange_TheNEWControllerLooksAndDiscards
#// TS26_26 Mother Talzin — her When Defeated resolves for whoever controls her when she dies. P2 plays
#// No Glory, Only Results (JTL_043) to take Talzin and defeat her, so P2 is the one who looks at "an
#// opponent's hand" — P1's — and discards from it. P1's only card goes to their discard and P1 draws a
#// replacement, ending back at one card in hand with an empty arena. P1's discard holds TWO cards:
#// the discarded SOR_095 AND Talzin herself (defeated to her owner's pile).
#// Same rule as Sith Traditions: the When Defeated reads from the CURRENT controller.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6;handCardIds:SOR_095}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: TS26_26:1:0
WithP2Hand: JTL_043
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirHand-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P1GROUNDARENACOUNT:0
