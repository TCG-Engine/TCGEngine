# OnAttack_TakeAnEvent_PutItOnTop
#// IC27_138 Banshee (Space 2/3, cost 2, Cunning/Villainy) — "On Attack: You may search the top 5 cards of your
#// deck for a non-unit card, reveal it, and put it on top of your deck. (Put the other cards on the bottom of your
#// deck in a random order.)"
#// Deck (top first): Battlefield Marine (unit), TIE/ln (unit), Confiscate (event), Academy Training (upgrade),
#// Independent Smuggler (a Pilot — a UNIT out of play, CR 7.5.17 Piloting), then Crushing Blow (event) sixth, outside the
#// search. P1 takes Confiscate: it is revealed (logged by name) and goes on top; the deck stays at 6 cards; the
#// attack then deals its 2 to the base.
#// COVERAGE: offer=Offer_NonUnitsInTheTopFiveOnly (event + upgrade offered; a unit, a Pilot unit and the 6th card
#//           not) · decline=Decline_TheTopFiveStayPut (the "may" covers the whole search) vs
#//           SearchAndTakeNothing_AllFiveGoToTheBottom (a searched-but-empty pick still bottoms the five) ·
#//           boundary=top 5 vs the 6th card (Offer section) + ShortDeck_SearchesWhatIsThere + EmptyDeck_NoPrompt ·
#//           no target=NoNonUnitInTheTopFive_TheFiveStillGoToTheBottom · trigger window=WhenDefending_NoSearch ·
#//           control=ControlChange_SearchesTheControllersDeck · reqboundary=AcrossARequestBoundary ·
#//           modifier=ArcanaStarMap_SearchesTheTopTen (ASH_084 doubles the count) ·
#//           value class=TakeAnUpgrade_PutItOnTop (upgrade as well as event) ·
#//           modes=2P only ("your deck" = the controller's own; no player reference, no friendly/enemy)

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_251

## EXPECT
P1DECKTOPCARD:SOR_251
P1DECKCOUNT:6
LOGCONTAINS:[[SOR_251|Confiscate]] on the top of their deck
P2BASEDMG:2
P1NODECISION

---

# Offer_IsAYesNoBeforeAnyCardIsSeen
#// The "may" is asked first, while nothing has been searched: the YESNO is pending and the On Attack has not
#// yet let the attack deal its damage.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECISIONTYPE:YESNO
P2BASEDMG:0
P1DECKCOUNT:6

---

# Offer_NonUnitsInTheTopFiveOnly
#// The search is left pending so its legal picks are asserted directly. Offered: Confiscate (event) and Academy
#// Training (upgrade). Not offered: Battlefield Marine (unit), Independent Smuggler (a Pilot is a UNIT outside
#// play — Piloting does not make it a non-unit card) and Crushing Blow, an event but the SIXTH card.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTYPE:TOPDECKSEARCH
P1SEARCHPLAYABLEHAS:SOR_251
P1SEARCHPLAYABLEHAS:SOR_120
P1SEARCHPLAYABLENOT:SOR_095
P1SEARCHPLAYABLENOT:SOR_225
P1SEARCHPLAYABLENOT:JTL_211
P1SEARCHPLAYABLENOT:LOF_077

---

# TakeAnUpgrade_PutItOnTop
#// "a non-unit card" covers upgrades as well as events: Academy Training goes on top.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_120

## EXPECT
P1DECKTOPCARD:SOR_120
P1DECKCOUNT:6
P2BASEDMG:2

---

# Decline_TheTopFiveStayPut
#// Declining the "may" searches nothing: the deck is untouched (Battlefield Marine still on top) and the attack
#// resolves normally. Contrast SearchAndTakeNothing, where the same deck loses its top five to the bottom.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:6
P2BASEDMG:2
P1NODECISION

---

# SearchAndTakeNothing_AllFiveGoToTheBottom
#// Searching and choosing nothing (CR 8.26.1 — a search for an attribute may come up empty) still puts the five
#// searched cards on the bottom: the sixth card, Crushing Blow, is now on top.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1DECKTOPCARD:LOF_077
P1DECKCOUNT:6
P2BASEDMG:2
P1NODECISION

---

# NoNonUnitInTheTopFive_TheFiveStillGoToTheBottom
#// The top five are all units (Imperial Dark Trooper, Kanan, a Pilot among them). The YESNO is still asked — the
#// player cannot know before searching — and accepting it bottoms the five with nothing to take.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 JTL_211 IC27_121 SEC_080 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1DECKTOPCARD:LOF_077
P1DECKCOUNT:6
P2BASEDMG:2
P1NODECISION

---

# ShortDeck_SearchesWhatIsThere
#// Three cards left: the search takes all three, Confiscate goes on top, and the deck is still three cards.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_251

## EXPECT
P1DECKTOPCARD:SOR_251
P1DECKCOUNT:3
P2BASEDMG:2

---

# EmptyDeck_NoPrompt
#// CR 8.6.2 (searching an empty deck is ignored): with no deck there is nothing to ask, so no YESNO is raised and
#// the attack deals its damage at once.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P2BASEDMG:2
P1NODECISION

---

# WhenDefending_NoSearch
#// "On Attack" is Banshee's own attack only: P2's TIE/ln attacks Banshee and P1 is asked nothing; the deck is
#// untouched.

## GIVEN
CommonSetup: yyk/bbw
WithActivePlayer: 2
WithP1SpaceArena: IC27_138:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P2>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:6
P1NODECISION
P2NODECISION
TURNPLAYER:1

---

# ControlChange_SearchesTheControllersDeck
#// P1 controls P2's Banshee. "your deck" is the CONTROLLER's: P1's deck is searched and Confiscate goes on top of
#// it, while P2's deck (topped by Vanquish, an event the search would offer) is untouched.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArenaControlled: IC27_138:2
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]
WithP2Deck: [SOR_078 SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_251

## EXPECT
P1DECKTOPCARD:SOR_251
P1DECKCOUNT:6
P2DECKTOPCARD:SOR_078
P2DECKCOUNT:3
P2BASEDMG:2

---

# AcrossARequestBoundary
#// The YESNO and the pick are separate requests; the searched cards ride the finalize's own param, so a request
#// boundary between them loses nothing.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1Deck: [SOR_095 SOR_225 SOR_251 SOR_120 JTL_211 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_251

## EXPECT
P1DECKTOPCARD:SOR_251
P1DECKCOUNT:6
P2BASEDMG:2
P1NODECISION

---

# ArcanaStarMap_SearchesTheTopTen
#// ASH_084 Arcana Star Map on Banshee — "if you would search a number of cards from your deck, search twice that
#// number instead" — makes it the top 10: Crushing Blow, the sixth card, is now offered and goes on top.

## GIVEN
CommonSetup: yyk/bbw
P1OnlyActions: true
WithP1SpaceArena: IC27_138:1:0
WithP1SpaceArenaUpgrade: 0:ASH_084
WithP1Deck: [SOR_095 SOR_225 JTL_211 IC27_121 SEC_080 LOF_077]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:LOF_077

## EXPECT
P1DECKTOPCARD:LOF_077
P1DECKCOUNT:6
