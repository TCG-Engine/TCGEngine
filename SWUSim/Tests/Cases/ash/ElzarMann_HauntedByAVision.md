# DistributeThenOppSearch
#// ASH_224 Elzar Mann (Ground, 3/7, cost 6) — When Played: distribute up to 5 Advantage tokens among other
#// friendly units; then an opponent searches twice that many cards from the top of their deck for an event,
#// reveals it, and draws it. P1 gives SOR_095 2 Advantage, so P2 searches the top 4 and draws ASH_136 (event).
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_063 ASH_136 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2
- P2>AnswerDecision:ASH_136
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P2HANDCOUNT:1

---

# EntersReady_WithForceLeader
#// ASH_224 Elzar Mann — "While you control a Force leader, this unit enters play ready." With SOR_005 (Luke,
#// Force) as leader, Elzar enters ready. (Distribute 0 Advantage → no opponent search.)
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_005}
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_224
P1GROUNDARENAUNIT:0:READY

---

# EntersExhausted_NoForceLeader
#// ASH_224 Elzar Mann — without a Force leader he enters exhausted like any unit. SOR_001 (Krennic) is not
#// a Force leader.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_001}
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_224
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ZeroTokens_NoOpponentDraw
#// ASH_224 Elzar Mann — distributing 0 Advantage means the opponent searches "twice that many" = 0 cards, so
#// they draw nothing.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_001}
WithActivePlayer: 1
WithP2Deck: [ASH_136 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2HANDCOUNT:0
P2DECKCOUNT:4

---

# NoEventInSearch_NoDraw
#// ASH_224 Elzar Mann — distributing 2 makes the opponent search the top 4, but if none is an event they
#// draw nothing. P2's top 4 are all units.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_001}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P2HANDCOUNT:0
