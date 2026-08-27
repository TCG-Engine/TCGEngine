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

---

# DistributeFive_MultiUnit_Search10
#// ASH_224 Elzar Mann — all 5 Advantage tokens may be spread across multiple other friendly units (never
#// Elzar himself). Spreading 2/2/1 across three units makes the opponent search the top 10 (2 x 5); the
#// event ASH_136 is within, and P2 draws it.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_005}
WithActivePlayer: 1
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2Deck: [SOR_063 SOR_063 ASH_136 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2,myGroundArena-1:2,mySpaceArena-0:1
- P2>AnswerDecision:ASH_136
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P2HANDCOUNT:1

---

# OpponentDeclinesAvailableEvent
#// ASH_224 Elzar Mann — the opponent's draw is optional even when an event IS present. Distributing 2 makes
#// P2 search the top 4 (ASH_136 event included), but P2 declines, drawing nothing.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_005}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [ASH_136 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2
- P2>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P2HANDCOUNT:0
P2DECKCOUNT:4

---

# EmptyDeck_SkipSearch
#// ASH_224 Elzar Mann — with the opponent's deck empty, the search step is skipped entirely; tokens are still
#// placed and P2 draws nothing.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_005}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: []
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:5
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:5
P2HANDCOUNT:0

---

# BoundarySearchStopsAtTwice
#// ASH_224 Elzar Mann — the search covers EXACTLY twice the tokens distributed. Distributing 3 searches the
#// top 6; the only event (ASH_136) sits at position 7, outside the searched window, so P2 draws nothing.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224;myLeader:SOR_005}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 ASH_136]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P2HANDCOUNT:0

---

# NoEventSearched_CardsReturnToDeck
#// ASH_224 — the opponent's search for an event only LOOKS at the cards; when the window contains no event,
#// nothing is taken and the revealed cards stay in the deck (a search never mills). P1 distributes 1 Advantage
#// (Elzar himself present as the only other unit) → opponent searches top 2 of a 5-card no-event deck; deck
#// count stays 5, opponent's hand/discard unchanged.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:ASH_224}
WithP1GroundArena: SOR_046:1:0
WithP2Deck: [SOR_046 SOR_095 SOR_063 SOR_108 SOR_232]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2
## EXPECT
P2DECKCOUNT:5
P2HANDCOUNT:0
P2DISCARDCOUNT:0

---

# PlayedFromOpponentDeck_NoForceLeader_EntersExhausted
#// ASH_224 Elzar Mann — same take-and-play via LAW_215 Vermillion, but P1's leader SOR_001 (Krennic) is NOT a
#// Force leader. Elzar enters P1's control exhausted like any unit. (Distribute 0 Advantage → no search.)
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_001}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: [ASH_224 SOR_063 SOR_063]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_224
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# TwinSuns_TheUNSEARCHABLESeatStaysInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-24. Asserts the MENU.
#// "…then AN OPPONENT searches twice that many cards for an event, reveals it, and DRAWS it."
#// ⚠⚠ THIS CLAUSE HELPS THE CHOSEN OPPONENT — they get a free event. So an opponent who CANNOT search
#// (empty deck) is the caster's BEST answer, not a dead one. $eligible must stay null: filtering would
#// delete the strongest line and, with one carded opponent left, auto-resolve onto the WORST target with
#// no prompt at all. Same rule as TWI_222/TS26_43 — read what happens when the chosen player can't act.
#// SEAT 4 has an EMPTY deck and must still be offered.
#// ⚠ FIXTURE: keep the existing section's yyk/yyk aspects and the handCardIds form — ASH_224 is Cunning
#//   and an off-aspect deck pushes cost 6 past the pool, so the unit is never played.
#// Mutation check: filter to opponents with a non-empty deck and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_095 SOR_046]
WithP3Deck: [SOR_095 SOR_046]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1
