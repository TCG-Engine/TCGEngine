# Play2and1CostUnits
#// COVERAGE: offer=SearchFilter_NonVillainyPickIsREFUSED (the aspect filter holds on the SERVER, not
#//           just in the offered list) + SearchFilter_CombinedCostBudgetIsENFORCED (the budget is a
#//           second, independent mechanism) + ControlChange_AmbushPoolIsByCONTROLLER_NotOwner (the
#//           Ambush target pool asserted exactly, left pending) ·
#//           reqboundary=NestedSearcherSeesThePOSTSearchDeck_NotTheDisplayLeftovers (trigger-order
#//           pick, search answer, the fetched unit's own scry answer and the Ambush YES/NO are four
#//           separately serialized answers; the second search must read the deck as the first LEFT it,
#//           and the deck count is the teeth) · control=ControlChange_AmbushPoolIsByCONTROLLER_NotOwner
#//           (both halves of a take-control end state on the board: a P2-owned unit under P1's control
#//           is NOT an Ambush target, a P1-owned unit under P2's control IS) · boundary
#//           pair=Play2and1CostUnits (2+1=3, exactly AT the combined-cost budget → both play) vs
#//           SearchFilter_CombinedCostBudgetIsENFORCED (2+2=4, one over → only one plays);
#//           PlayThree1CostUnits pins that "any number" is not capped at two ·
#//           decline=TakeNothing_DeckIsReturnedNotMilled ("any number" includes ZERO with legal picks
#//           PRESENT — the honest decline, not a fizzle) + the trailing NO that declines the Ambush in
#//           every section above, standing alone in Ambush terms.
#// SOR_087 Darth Vader — WhenPlayed search top 10: play one 2-cost and one 1-cost Villainy unit for free.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_087
WithP1Resources: 7
WithP1Deck: SOR_226
WithP1Deck: SOR_225
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_226,SOR_225
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1DECKCOUNT:10

---

# Play3CostUnit
#// SOR_087 Darth Vader — WhenPlayed search top 10: play one 3-cost Villainy unit for free.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_087
WithP1Resources: 7
WithP1Deck: SOR_229
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_229
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:0
P1DECKCOUNT:11

---

# PlayThree1CostUnits
#// SOR_087 Darth Vader — WhenPlayed search top 10: play three 1-cost Villainy units for free.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_087
WithP1Resources: 7
WithP1Deck: SOR_225
WithP1Deck: SOR_225
WithP1Deck: SOR_225
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_225,SOR_225,SOR_225
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:3
P1DECKCOUNT:9

---

# SearchFilter_NonVillainyPickIsREFUSED
#// "any number of [Villainy] units" — the aspect filter must hold on the SERVER, not only in the client's
#// offer list. The search decision returns a list of CardIDs and the finalize resolves it against every
#// PEEKED card, so before this was fixed anything answered with was played: a non-Villainy unit here, and
#// on HMW_043 a cost-3 EVENT was placed into the ground arena as a unit.
#// SEC_237 Supreme Council Aide (Villainy, cost 1) is legal and lands; SOR_095 Battlefield Marine
#// (Command/Heroism) is answered for in the same breath and must NOT. Arena = Vader + SEC_237 only.
#// The trailing NO declines Vader's own Ambush attack.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SEC_237 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_237,SOR_095
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_087
P1GROUNDARENAUNIT:1:CARDID:SEC_237

---

# SearchFilter_CombinedCostBudgetIsENFORCED
#// "with combined cost 3 or less" is the card's OTHER gate, and it lived only in the constraint string
#// sent to the client. Two SEC_080 Imperial Dark Troopers (Villainy, cost 2 each) both pass the aspect
#// filter, but 2 + 2 = 4 exceeds the budget: exactly ONE may be played.
#// The pair to the section above — that one proves the FILTER is enforced, this one the CONSTRAINT; they
#// are separate mechanisms and a fix to either alone leaves the other open.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SEC_080 SEC_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080,SEC_080
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_087
P1GROUNDARENAUNIT:1:CARDID:SEC_080

---

# FetchedUnitsWhenPlayedFiresImmediately
#// "play each of them for free" is a REAL play: the fetched unit's own When Played resolves. SHD_080
#// Salacious Crumb is Command/VILLAINY (legal for Vader's Villainy filter) with a mandatory "heal 1 from
#// your base" — the pre-damaged base ends at 4. Under the old put-into-play placement it stayed at 5.
#// The trailing NO declines Vader's own Ambush attack.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1BASEDMG:4

---

# TakeNothing_DeckIsReturnedNotMilled
#// "any number" includes ZERO, with legal picks PRESENT (two cost-1 Villainy units in the top 10) — the
#// honest decline, distinct from a fizzle with nothing to take. Nothing is played, all 10 peeked cards
#// return to the deck, and Vader still offers (and here declines) his Ambush attack.
#// Previously this was waved off as covered by other cards' decline sections; this card's own search
#// flows through the same finalize but with its own filter+budget, so it earns its own section.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SOR_225 SEC_237 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P1DECKCOUNT:10

---

# PlayOnlyOneCard_OtherLegalPickReturnsToDeck
#// "any number … with combined cost 3 or less" — one pick is legal while a SECOND legal pick sits in the
#// pool. SEC_237 (Villainy c1, ground) is played; SOR_225 (Villainy c1, space) is left and returns to
#// the deck with the rest. Deck 10 → 9 proves exactly one card left it.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SEC_237 SOR_225 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_237
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_237
P1SPACEARENACOUNT:0
P1DECKCOUNT:9

---

# NestedSearcherSeesThePOSTSearchDeck_NotTheDisplayLeftovers
#// Two deck-inspecting abilities NESTED: Vader's search plays SOR_031 Inferno Four, whose own
#// When Played ("look at the top 2 cards of your deck…") fires mid-loop — and must see the deck AS
#// VADER'S SEARCH LEFT IT: the 8 unpicked events already on the bottom, so the top 2 are the two cards
#// that sat BELOW the 10-card peek window (SOR_046 at 11, SOR_095 at 12). A scry reading the
#// pre-search order, or contaminated by Vader's display set, peeks SOR_171s instead.
#// THE DECK COUNT IS THE TEETH: the scry answer names SOR_046,SOR_095 as the cards to keep on top, and
#// SCRY_FINALIZE silently DROPS any answered ID that was not actually peeked — the peeked cards are
#// already spliced off the deck, so a wrong peek + this answer loses two cards and the deck ends at 8,
#// not 10. Both picks (Inferno 2 + TIE 1 = 3) fit the budget; Inferno's uncovered Vigilance pip is
#// free-play-irrelevant. The trailing NO declines Vader's Ambush.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SOR_031 SOR_225 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_046 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_031,SOR_225
- P1>AnswerDecision:SOR_046,SOR_095|
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_031
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1DECKCOUNT:10
P1NODECISION

---

# ControlChange_AmbushPoolIsByCONTROLLER_NotOwner
#// SOR_087 Darth Vader — "Ambush (…it may ready and attack an ENEMY unit)". Which units are "enemy"
#// is decided by CONTROL, not ownership, and both halves of a take-control end state are on the board
#// at once: P1 controls a SOR_095 that P2 OWNS, and P2 controls a SOR_128 that P1 OWNS. Intended pool:
#// only the two units sitting in P2's arena, in P1's "their" frame — the stolen marine on P1's own
#// side is friendly and must be absent even though the opponent owns it, and P1's own Stormtrooper
#// under P2's control must be present even though P1 owns it. An owner-keyed enemy test inverts both,
#// which is the same COUNT of targets, so only an exact-set assertion catches it.
#// Flow note: with enemy units on the board Vader's When Played search and his Ambush are two live
#// triggers, so the play opens with a trigger-order pick (EffectStack-0 = the search). The search is
#// then declined ('-', legal picks present) so the board under test is exactly the seeded one, and
#// the Ambush is accepted; the target decision is left PENDING so the offer can be read.
#// P1DECKCOUNT:10 and P1GROUNDARENACOUNT:2 pin that the declined search took nothing.

## GIVEN
CommonSetup: ggk/ggk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_087
WithP1Deck: [SOR_225 SEC_237 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaControlled: SOR_128:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1DECKCOUNT:10
P1GROUNDARENACOUNT:2
