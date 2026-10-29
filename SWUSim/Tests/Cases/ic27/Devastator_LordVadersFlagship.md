# SearchTopFive_PlaysAVillainyUnitForFree
#// IC27_068 Devastator — Lord Vader's Flagship (9/9 Space, Command/Villainy, Imperial·Vehicle·Capital Ship, unique)
#// "Overwhelm. When Played: Search the top 5 cards of your deck for a Villainy unit that costs 12 or less and play
#// it for free. (Put the other cards on the bottom of your deck in a random order.)"
#// Overwhelm is printed (auto-wired). DoTopDeckPlay(5, Villainy UNIT with printed cost <= 12, budget 12, max 1) —
#// the SOR_087 search/play finalize, whose filter and count/cost constraint are re-checked server-side.
#//
#// COVERAGE: offer=IllegalPicks_AreRefused (a Heroism unit, an event and a 6th-card Villainy unit are all refused —
#//           the TOPDECKSEARCH offer is a CardID list, so refusal is asserted behaviourally) ·
#//           decline=ChooseNone_AllFiveToTheBottom ·
#//           boundary=SearchTopFive_PlaysAVillainyUnitForFree (cost 12 — LOF_089 — is legal; NO Villainy unit costs
#//           13+, so the upper side cannot be built) + IllegalPicks_AreRefused (the 6th card is out of the window) ·
#//           control=N/A ("your deck" is the resolver's; Devastator's When Played resolves for whoever played it) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// Deck top-first: Marine (Heroism), Dark Trooper (Villainy 2), Confiscate (event), LOF_089 Supremacy (Villainy 12),
#// Stormtrooper (Villainy 1), then a TIE/ln (6th). P1 picks LOF_089: it is played for free with 0 resources left
#// (10 spent on Devastator). The other four go to the bottom, so the TIE/ln is now on top.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_089

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:LOF_089
P1RESAVAILABLE:0
P1DECKCOUNT:5
P1DECKTOPCARD:SOR_225
P1NODECISION

---

# OffAspectCheapUnit_AlsoFree
#// A Death Star Stormtrooper (Aggression/Villainy, cost 1 — off-aspect here) is played free: no cost, no penalty.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:0

---

# IllegalPicks_AreRefused
#// The search's filter is enforced on the answer: a Heroism unit (Marine), an event (Confiscate) and the 6th card
#// (TIE/ln, outside the window) are all refused — nothing enters play.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1DECKCOUNT:6

---

# VillainyEventPick_Refused
#// "a Villainy UNIT": SOR_235 is a Villainy EVENT — it passes the aspect half of the filter and is refused by the
#// type half. It stays in the deck (to the bottom), never resolving or reaching the discard.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_235 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_235

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:6

---

# SixthCard_OutOfWindow_Refused

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_225

## EXPECT
P1SPACEARENACOUNT:1
P1DECKTOPCARD:SOR_225

---

# ChooseNone_AllFiveToTheBottom

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1DECKCOUNT:6
P1DECKTOPCARD:SOR_225
P1NODECISION

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SEC_080

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1DECKCOUNT:5

---

# NestedPlay_ClosesTheActionOnce

## GIVEN
CommonSetup: ggk/rrk/{myResources:10}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_068
WithP1Deck: [SOR_095 SEC_080 SOR_251 LOF_089 SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080

## EXPECT
P1GROUNDARENACOUNT:1
TURNPLAYER:2
NOEXTRAACTION
