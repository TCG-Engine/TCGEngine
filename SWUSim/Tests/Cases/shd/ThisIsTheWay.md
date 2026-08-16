# SearchTop8_Mando_Upgrade
#// SHD_253 (2-cost Heroism event) — "Search the top 8 cards of your deck for up to 2 Mandalorian and/or
#// upgrade cards, reveal them, and draw them." Top of deck has one Mandalorian unit (SOR_142) and one
#// upgrade (SOR_069) among event fillers → both drawn; the 2 fillers go to the bottom.
#// COVERAGE: offer=SearchOffer_BothFilterArmsAndNothingElse (the top-8 search is a card-ID display, not an
#//           mzID target choice, so SELECTABLE* cannot address it — SEARCHPLAYABLEHAS/NOT reads the pending
#//           search's playable set instead, with BOTH arms of the "Mandalorian and/or upgrade" OR present
#//           and every card matching NEITHER absent) + IneligiblePick_BehavesAsTakeNothing (the same legal
#//           set proved by its EFFECT — an out-of-filter answer draws nothing) ·
#//           reqboundary=RequestBoundary_FilterAndCountSurvive (the stored filter AND the up-to-2 cap are
#//           re-read from serialized state after the boundary) · control=N/A (an event has no persistent
#//           object and the search resolves only for the player who played it — there is nothing whose
#//           control could change) · boundary pair=EighthCardIsWithinReach (a match at depth 8 -> offered
#//           and drawn) vs NinthCardIsOutOfReach (the same match one card deeper -> unreachable, nothing
#//           drawn), plus the count cap OverCap_OnlyTheFirstTwoAreDrawn and the low-end
#//           DeckSmallerThanSearchDepth_StillFindsTheMatch · decline=TakeNothing_AllEightToBottom (the
#//           "up to 2" soft pass is an amount of ZERO taken with three legal finds on the table — the
#//           TARGET choice itself is never declinable)

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: SOR_142
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_142,SOR_069

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:2

---

# SearchOffer_BothFilterArmsAndNothingElse
#// SHD_253 — the filter is a TWO-ARM OR (Mandalorian trait OR card type Upgrade) and it is the
#// load-bearing half of the card. The top 8 carry one card for each arm plus one that satisfies both:
#// SHD_056 Follower of The Way (Mandalorian GROUND UNIT, not an upgrade), SOR_069 Resilient (an UPGRADE
#// whose traits are Innate — not Mandalorian) and SHD_069 Foundling (a Mandalorian upgrade, both arms at
#// once). They are mixed among five cards matching NEITHER arm: SOR_095 Battlefield Marine (Rebel/Trooper
#// unit) and SOR_171 Mission Briefing (a Plan event). The section stops on the pending search so the OFFER
#// itself is the assertion — all three matches playable, both non-matching cards withheld.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SHD_056
P1SEARCHPLAYABLEHAS:SOR_069
P1SEARCHPLAYABLEHAS:SHD_069
P1SEARCHPLAYABLENOT:SOR_095
P1SEARCHPLAYABLENOT:SOR_171
P1HANDCOUNT:0

---

# MandalorianArm_PickOnlyOne_OtherSevenToBottom
#// SHD_253 — "up to 2" includes ONE. From the same discriminating top 8 as
#// SearchOffer_BothFilterArmsAndNothingElse, P1 takes only the Mandalorian non-upgrade SHD_056 and leaves
#// the two upgrades behind: hand 1, and all seven unpicked cards return to the bottom so the deck is back
#// to 7. Per the card's reminder text they return in a RANDOM order, so only the COUNT is asserted.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_056

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SHD_056
P1DECKCOUNT:7
P1DISCARDCOUNT:1
P1NODECISION

---

# UpgradeArm_PickOnlyOne_TypeTestNotTraitTest
#// SHD_253 — the second arm is a card-TYPE test, not another trait test. SOR_069 Resilient is an Upgrade
#// with the trait Innate and no Mandalorian anywhere on it, and it is still a legal find. P1 takes it
#// alone from the discriminating top 8; the other seven cards go to the bottom.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_069
P1DECKCOUNT:7
P1DISCARDCOUNT:1
P1NODECISION

---

# TakeNothing_AllEightToBottom
#// SHD_253 — the soft-pass branch of "up to 2". Three legal finds are sitting in the top 8 and P1 takes
#// NONE of them: the amount taken is zero, nothing is drawn, and all eight revealed cards return to the
#// bottom of the deck with the count unchanged at 8. The event is still spent — it sits in the discard and
#// its 2 resources stay paid.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# IneligiblePick_BehavesAsTakeNothing
#// SHD_253 — the two-arm filter is server-enforced, not a client hint. P1 answers with SOR_095 Battlefield
#// Marine and SOR_171 Mission Briefing, neither of which is Mandalorian and neither of which is an
#// upgrade. Both are dropped: nothing is drawn and all eight peeked cards go back to the bottom, exactly
#// as if P1 had taken nothing. Intended: an ineligible answer must behave exactly like "take nothing".

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_171

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1
P1NODECISION

---

# OverCap_OnlyTheFirstTwoAreDrawn
#// SHD_253 — "up to 2" is a hard CAP, enforced server-side in the order the picks were made. P1 answers
#// with THREE legal finds (SHD_056, SOR_069, SHD_069): the first two are drawn and the third overflows
#// into the same disposition as an unpicked card — the bottom of the deck. Hand 2, deck 6.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_056,SOR_069,SHD_069

## EXPECT
P1HANDCOUNT:2
P1HANDCARD:0:SHD_056
P1HANDCARD:1:SOR_069
P1DECKCOUNT:6
P1DISCARDCOUNT:1
P1NODECISION

---

# EighthCardIsWithinReach
#// SHD_253 — the depth boundary, inclusive half. SOR_069 Resilient is the EIGHTH card of a 9-card deck:
#// exactly at the search depth, so it is offered and drawn. The 7 non-matching cards above it go to the
#// bottom and the 9th card is never touched, leaving a deck of 8. Boundary partner of
#// NinthCardIsOutOfReach, which buries the identical card one card deeper.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SOR_171 SOR_095 SOR_171 SOR_095 SOR_171 SOR_095 SOR_069 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_069
P1DECKCOUNT:8
P1DISCARDCOUNT:1
P1NODECISION

---

# NinthCardIsOutOfReach
#// SHD_253 — the depth boundary, exclusive half. Identical fixture to EighthCardIsWithinReach except
#// SOR_069 Resilient is the NINTH card: one card past the search depth. It matches the upgrade arm
#// perfectly and is still unreachable — the offer holds nothing, the answer is dropped, and the hand stays
#// empty with the deck back at 9. Depth is enforced, not merely suggested.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SOR_171 SOR_095 SOR_171 SOR_095 SOR_171 SOR_095 SOR_171 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:9
P1DISCARDCOUNT:1
P1NODECISION

---

# DeckSmallerThanSearchDepth_StillFindsTheMatch
#// SHD_253 — "the top 8 cards" is a maximum, not a requirement. With only THREE cards left the search runs
#// over what is there: SHD_056 is found and drawn, and the two non-matching cards return to the bottom,
#// leaving a deck of 2. Low-end partner of EighthCardIsWithinReach / NinthCardIsOutOfReach.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SHD_056 SOR_095 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_056

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SHD_056
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# EmptyDeck_CleanNoOp
#// SHD_253 — searching an EMPTY deck is a clean no-op, not an error and not a stuck decision. P1 plays the
#// event with nothing at all in the deck: it still costs its 2 resources and still goes to the discard, no
#// search prompt is raised, nothing is drawn and the deck stays empty.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_253
P1RESAVAILABLE:0
P1NODECISION

---

# RequestBoundary_FilterAndCountSurvive
#// SHD_253 — the search spans two requests in production (reveal the top 8, then pick), so the stored
#// filter AND the up-to-2 cap must live in serialized state rather than in a transient global. Same
#// discriminating top 8 with a fresh-process boundary inserted between the play and the pick, and a
#// deliberately mixed answer: two ineligible cards (SOR_095, SOR_171) and two legal ones. A filter lost
#// across the boundary would let the ineligible pair through; a lost cap would draw three. Exactly the two
#// legal finds are drawn and the other six go to the bottom.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: [SOR_095 SHD_056 SOR_171 SOR_069 SOR_095 SOR_171 SHD_069 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_095,SHD_056,SOR_171,SOR_069

## EXPECT
P1HANDCOUNT:2
P1HANDCARD:0:SHD_056
P1HANDCARD:1:SOR_069
P1DECKCOUNT:6
P1DISCARDCOUNT:1
P1NODECISION
