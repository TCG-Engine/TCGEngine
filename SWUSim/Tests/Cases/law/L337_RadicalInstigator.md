# WhenPlayedSearchPlayDroids
#// LAW_063 L3-37 (3/2, Hidden) — When Played: search the top 10 cards for any number of Droid units with
#// combined cost 5 or less and play each for free. Two SEC_080 (Droid, cost 2 each = 4) on top are both
#// played; SOR_237 (non-Droid) is left.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
WithP1Deck: SEC_080
WithP1Deck: SEC_080
WithP1Deck: SOR_237
WithP1Hand: LAW_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080,SEC_080

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:1

---

# PlayedForFree_IgnoresTheASPECTPenalty
#// "play each of them for FREE" — free means 0, aspect penalty included. The grw/bgw setup covers
#// Command, Aggression and Heroism, so SEC_080 Imperial Dark Trooper (Command/VILLAINY) carries an
#// uncovered pip and would normally cost 2 + 2 = 4. It still lands.
#// L3-37 costs 6 out of 6, leaving 0: if the penalty were being charged the play could not happen at all,
#// which is what makes the resource assertion load-bearing rather than incidental.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1Deck: [SEC_080 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1RESAVAILABLE:0

---

# TakeNothing_DeckIsReturnedNotMilled
#// The decline branch: nothing is played and every peeked card goes back to the deck rather than being
#// milled. 10 seeded, 10 still there.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1Deck: [SEC_080 SEC_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_063
P1DECKCOUNT:10

---

# PILOTingDroidIsPlayedAsAUNIT_NotAsAPilot
#// A Piloting card IS a unit card, so it is a legal find for "any number of DROID units" — but it is
#// played AS A UNIT. The ability named what it was searching for and plays that; the pilot-upgrade mode
#// is never offered, even with a legal Vehicle host on the board.
#// JTL_245 R2-D2 is a cost-1 Droid with "Piloting [0 resources Heroism]". SEC_214 Skyhopper Canyon Runner
#// is a friendly Vehicle, so a pilot host exists — drop it and this section passes for the wrong reason.
#// R2-D2 must end up in the ground arena as its own unit, with the Vehicle carrying no upgrade.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1GroundArena: SEC_214:1:0
WithP1Deck: [JTL_245 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_245

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PlayedFromTheOpponentsDiscard_SearchOffersTheCASTERSDeck
#// LAW_063 L3-37 — "Search the top 10 cards of YOUR deck": the library belongs to whoever PLAYS the card,
#// not to whoever owns it. A unit played from your own hand has owner == controller, so the axis is
#// invisible there; SEC_205 Obi-Wan is what separates them. Obi-Wan's combat damage to P2's base mills the
#// top of P2's deck — L3-37 itself — into P2's discard and makes it playable from there, so P1 casts a
#// P2-OWNED L3-37 (cost 6, and the permission waives the Command/Aggression penalty against P1's
#// Cunning/Villainy side).
#//
#// The two libraries share no card, which is what makes the pending offer decisive:
#//   - P1's deck: SEC_080 x2 (Droid, cost 2) + SOR_237. SEC_080 exists ONLY here.
#//   - P2's deck: LAW_257 x3 after the mill. LAW_257 exists ONLY here (and is Underworld, not a Droid).
#// So SEC_080 in the playable set can only have come from P1's library, and LAW_257's absence says P2's
#// was never opened. The deck counts confirm it physically: P1's three cards are lifted out for the peek
#// (count 0 while the search is pending) while P2's stays at 3.
#//
#// The P1>Drain stands in for the caster's own client poll — the When Played search lands on P1's queue as
#// a CUSTOM when the card is played out of the opponent's discard, and is raised on the next drain (the
#// same shape the Maz Kanata relay sections use). Without it the search never surfaces.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 6
WithP1Deck: [SEC_080 SEC_080 SOR_237]
WithP2Deck: [LAW_063 LAW_257 LAW_257 LAW_257]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>Drain

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SEC_080
P1SEARCHPLAYABLENOT:LAW_257
P1DECKCOUNT:0
P2DECKCOUNT:3

---

# PlayedFromTheOpponentsDiscard_PlaysDroidsFromTheCASTERSDeck
#// LAW_063 L3-37 — the same P2-owned cast carried to the end state. Both SEC_080 (combined cost 4, within
#// the "5 or less" budget) are pulled out of P1's library and put into play for free, and the unpicked
#// SOR_237 goes back, leaving P1's deck at 1. P2's library is untouched at 3 and P2's board stays empty:
#// the fetched units enter under the CONTROLLER, and the P2-owned L3-37 itself sits in P1's arena. P1's 6
#// resources all went to L3-37's cost, so P1RESAVAILABLE:0 also holds the "for free" clause honest.
#// Reading the owner's seat anywhere in this chain would have dug through P2's LAW_257s — none of which is
#// a Droid — and produced nothing at all.
#//
#// COVERAGE: control=PlayedFromTheOpponentsDiscard_SearchOffersTheCASTERSDeck + this section (a P2-OWNED
#//           L3-37 cast by P1 via SEC_205's play-from-their-discard permission: "your deck" is the
#//           CASTER's library and the fetched Droids enter the CASTER's arena; both decks asserted) ·
#//           offer=PlayedFromTheOpponentsDiscard_SearchOffersTheCASTERSDeck (pending SEARCHPLAYABLE
#//           membership) + PILOTingDroidIsPlayedAsAUNIT_NotAsAPilot (the pilot-upgrade mode is never
#//           offered) · decline=TakeNothing_DeckIsReturnedNotMilled (answer "-", all 10 returned) ·
#//           boundary pair=WhenPlayedSearchPlayDroids (Droids within budget -> played) vs
#//           TakeNothing_DeckIsReturnedNotMilled (nothing taken -> deck intact) · reqboundary=the two
#//           sections above drive the trigger across a separate P1>Drain request, but no explicit
#//           SimulateRequestBoundary section exists

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 6
WithP1Deck: [SEC_080 SEC_080 SOR_237]
WithP2Deck: [LAW_063 LAW_257 LAW_257 LAW_257]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>Drain
- P1>AnswerDecision:SEC_080,SEC_080

## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:1:CARDID:LAW_063
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P2DECKCOUNT:3
P1RESAVAILABLE:0
