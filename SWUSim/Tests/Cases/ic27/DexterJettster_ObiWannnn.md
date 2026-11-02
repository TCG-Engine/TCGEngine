# PayOne_LookAtTopThree_Arrange
#// IC27_049 Dexter Jettster — Obi-Wannnn!!! (2/6 Ground, Vigilance, Fringe, unique)
#// "On Attack: You may pay [1 resource]. If you do, look at the top 3 cards of your deck. Put any number of them on
#// the bottom of your deck and the rest on top in any order."
#// Poggle's shape: YESNO (offered only when the payment capacity is >= 1 AND the deck holds a card — otherwise
#// accepting could only spend a resource for nothing) -> SWUPayInlineAbilityCost (Credits / SEC_122 Droids pay
#// as a last resort) -> DoScry(3), the engine's "look at the top N, any number to the bottom, rest on top in
#// any order" primitive.
#//
#// COVERAGE: offer=N/A (no target pool — the peeked cards are fixed by the deck; the SCRY window is pinned by
#//           OnlyTheTopThree_FourthCardUntouched) ·
#//           decline=Decline_NoPaymentNoLook ·
#//           boundary=ShortDeck_LooksAtTwo (2 of 3) + OnlyTheTopThree_FourthCardUntouched (3 of 4) ·
#//           control=N/A ("your deck" is the resolver's; a stolen Dexter resolves for its controller — the
#//           generic On Attack rule, no owner-scoped zone in the card) ·
#//           reqboundary=Scry_AnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// Deck top-first: Marine, Dark Trooper, Stormtrooper, Consular. Dexter pays 1 and puts the Stormtrooper on top,
#// the Marine and Dark Trooper on the bottom. Two resources -> one left.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_128|SOR_095,SEC_080

## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:4
P1RESAVAILABLE:1
P2BASEDMG:2
P1NODECISION

---

# OnlyTheTopThree_FourthCardUntouched
#// The window is exactly 3: bottoming all three peeked cards surfaces the never-peeked 4th (Consular).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:|SOR_095,SEC_080,SOR_128

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:4

---

# Decline_NoPaymentNoLook

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1DECKTOPCARD:SOR_095
P1RESAVAILABLE:2
P1NODECISION
P2BASEDMG:2

---

# CannotPay_NoOffer
#// No ready resource and no Credit: the "pay [1 resource]" cannot be done, so nothing is offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1DECKTOPCARD:SOR_095
P2BASEDMG:2

---

# CreditPaysTheOne
#// A Credit token pays an ability's resource cost (CR 3.13): with 0 ready resources and 1 Credit the offer IS
#// raised and the Credit is spent.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_128|SOR_095,SEC_080

## EXPECT
P1CREDITCOUNT:0
P1DECKTOPCARD:SOR_128

---

# EmptyDeck_NoOffer
#// Nothing to look at: paying could only waste a resource, so nothing is offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1RESAVAILABLE:2

---

# ShortDeck_LooksAtTwo
#// Only two cards: he looks at both; putting the Marine on the bottom leaves the Dark Trooper on top.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_080|SOR_095

## EXPECT
P1DECKTOPCARD:SEC_080
P1DECKCOUNT:2

---

# Scry_AnsweredAcrossRequestBoundary
#// The peeked cards stay in the deck until the finalize runs, so a request boundary before the answer loses
#// nothing.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_049:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_128|SOR_095,SEC_080

## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:4
