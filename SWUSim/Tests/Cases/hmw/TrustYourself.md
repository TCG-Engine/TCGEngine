# ShieldThenSearchTopThree
#// COVERAGE: offer=Offer_AnyUnit (the Shield pool); the search is the generic top-deck search
#//           decline=ChooseNone_DeckUnchanged (the search's "a card" may find none worth taking)
#//           boundary=N/A (STRUCTURAL: fixed depth 3 — the generic search's own sections pin depth)
#//           independence=NoUnits_SearchStillHappens · empty=EmptyDeck_ShieldOnly
#//           control=N/A · reqboundary=AcrossTheRequestBoundary · modes=2P only (no player reference)
#//
#// HMW_101 Trust Yourself — Event, cost 2, [Vigilance], Innate.
#// "Give a Shield token to a unit. Search the top 3 cards of your deck for a card and draw it."

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:SEC_080

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:3

---

# Offer_AnyUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# NoUnits_SearchStillHappens

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# ChooseNone_StillTakesACard
#// HMW_101 Trust Yourself — "Give a Shield token to a unit. Search the top 3 cards of your deck for a card and
#// draw it." P1 answers the search with no pick: the Shield is given and a card is still drawn.
#// CR v9.0 8.26.1: only a search that SPECIFIES AN ATTRIBUTE may come up empty (the deck is hidden from the
#// opponent); "for a card" has none, so declining still takes a card — the first one (owner ruling 2026-10-03).
#// Before v9 this section asserted that taking nothing was allowed.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# EmptyDeck_ShieldOnly

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:0
P1BASEDMG:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_128

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1
