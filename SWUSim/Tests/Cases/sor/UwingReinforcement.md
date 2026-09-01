# SearchPlayTwoUnits
#// COVERAGE: offer=PlayOnlyOneCard + SearchPlayTwoUnits (two legal candidates in the top 10, so the pick
#//           is a genuine multi-select and not an auto-resolve; taking one leaves the other unplayed) +
#//           PILOTingUnitIsPlayedAsAUNIT_NotAsAPilot (a Piloting card is IN the "units" pool, and is
#//           played as a unit) + CombinedCostBoundary_EightIsRefused (the pool's cost gate, proven by an
#//           over-budget pick being refused rather than by picking a legal one) ·
#//           boundary=CombinedCostBoundary_SevenExactlyIsPlayed + CombinedCostBoundary_EightIsRefused
#//           (the combined-cost ceiling at exactly 7 and one point over) and
#//           CountCapOfTHREE_EvenWhenTheBudgetAllowsMore + TakeNothing_DeckIsReturnedNotMilled (the
#//           independent count cap at 3 and at 0) ·
#//           decline=TakeNothing_DeckIsReturnedNotMilled ("up to 3" includes ZERO — nothing played, all
#//           ten peeked cards back in the deck rather than milled) ·
#//           reqboundary=SimulateRequestBoundary_SearchConstraintsSurviveTheAnswer (the peeked IDs, the
#//           units-only filter and the ≤3/≤7 constraint all rebuilt from serialized state) ·
#//           control=CrossPlayer_SearchesTHEIROwnDeckAndPlaysIntoTHEIRArena (the who-resolves-it
#//           reading: "YOUR deck", the arena the fetched units enter and the discard the event lands in
#//           all key off the CASTER's seat, with P1's stocked deck and empty arena as the control. The
#//           owner≠controller reading is N/A: the fetched units are played from their own owner's deck,
#//           so the caster is necessarily both owner and controller and no split can exist)
#// SOR_104 U-Wing Reinforcement (Event, cost 7) — Search the top 10 of your deck for up to 3
#// units with combined cost 7 or less and play each for free. The top 10 hold two Battlefield
#// Marines (cost 2 each, combined 4 ≤ 7) among event fillers; both are played free into the
#// ground arena. The U-Wing event goes to discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1

---

# PlayOnlyOneCard
#// "up to 3 units" — one pick is legal with two legal candidates present. The unpicked Marine is not
#// played and goes back to the bottom with the rest.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_095 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DECKCOUNT:9

---

# TakeNothing_DeckIsReturnedNotMilled
#// "up to 3" includes ZERO: nothing is played and all 10 peeked cards return to the deck rather than
#// being milled. The event itself still goes to the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_095 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:10
P1DISCARDCOUNT:1

---

# CountCapOfTHREE_EvenWhenTheBudgetAllowsMore
#// Two independent limits — "up to 3 units" AND "combined cost 7 or less" — and this pins the COUNT one
#// on its own. Four SOR_108 Vanguard Infantry cost 1 each, so all four together are 4, comfortably inside
#// the 7 budget; only three may still be played. A cap implemented purely as a cost budget passes every
#// other section and fails here.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_108 SOR_108 SOR_108 SOR_108 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_108,SOR_108,SOR_108,SOR_108

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:7

---

# PILOTingUnitIsPlayedAsAUNIT_NotAsAPilot
#// A Piloting card IS a unit card, so it is a legal find for "up to 3 units" — but it is played AS A
#// UNIT, with no Unit-vs-Pilot choice, even though a legal Vehicle host is on the board.
#// JTL_093 Nien Nunb (cost 1, Command/Heroism, "Piloting [1 resource Command Heroism]") must land in the
#// ground arena as its own unit and SEC_214 Skyhopper Canyon Runner must end with no upgrade.
#// The Vehicle is load-bearing: without a legal pilot host the choice could not arise anyway.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1GroundArena: SEC_214:1:0
WithP1Deck: [JTL_093 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_093

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# FetchedUnitsWhenPlayedFiresImmediately
#// "play each of them for free" is a REAL play, so a fetched unit's own When Played resolves — nested,
#// before the game moves on. SHD_080 Salacious Crumb (cost 1) carries a MANDATORY "When Played: heal 1
#// damage from your base": the pre-damaged base must end at 4. Under the old put-into-play placement
#// this stayed at 5 (no trigger, no ceremony) — the probe that first proved the bug.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1BASEDMG:4

---

# TwoFetchedUnits_BothWhenPlayedsResolveInOrder
#// The multi-pick case: each play's triggers drain BEFORE the next unit plays (the queue orders them),
#// so two Crumbs heal 2 total. A batch implementation that placed both and fired triggers once — or
#// jumped the second play over the first's pending trigger — lands on 5 or 4 instead of 3.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SHD_080 SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080,SHD_080

## EXPECT
P1GROUNDARENACOUNT:2
P1BASEDMG:3

---

# CombinedCostBoundary_SevenExactlyIsPlayed
#// SOR_104 U-Wing Reinforcement — BOUNDARY axis, the legal side of "combined cost 7 OR LESS". The budget
#// is an inclusive ceiling, so a single unit costing exactly 7 spends all of it and must still be played.
#// SOR_118 97th Legion (cost 7) is the only unit in the top 10; it lands in the ground arena and the
#// other nine peeked cards go back to the deck. Pairs with CombinedCostBoundary_EightIsRefused, which
#// runs the identical flow one point over the line — an off-by-one that read the budget as "< 7" would
#// pass that section and fail this one.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_118 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_118

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_118
P1DECKCOUNT:9
P1DISCARDCOUNT:1

---

# CombinedCostBoundary_EightIsRefused
#// SOR_104 U-Wing Reinforcement — BOUNDARY axis, the illegal side. One point over the ceiling and the
#// pick must be refused outright: SOR_088 Blizzard Assault AT-AT costs 8, so even as the ONLY unit in
#// the top 10 — and even though it satisfies every other clause (it is a unit, and it is one card, well
#// inside the "up to 3" cap) — it cannot be played. It is not merely left unplayed: a refused pick is
#// disposed of exactly like an unpicked card, so all ten peeked cards return to the deck and nothing is
#// milled. The event still resolves and still goes to the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_088 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_088

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:10
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_104

---

# CrossPlayer_SearchesTHEIROwnDeckAndPlaysIntoTHEIRArena
#// SOR_104 U-Wing Reinforcement — CONTROL axis, the "who resolves it" reading. "Search the top 10 cards
#// of YOUR deck ... and play each of them for free" is seat-relative three times over: which deck is
#// read, whose arena the fetched units enter, and whose discard the event lands in. P2 casts it while P1
#// also holds a ten-card deck stocked with legal targets, so a hardcoded seat-1 read would show up as
#// P1's deck being peeked or P1's arena filling. P2 takes both Battlefield Marines: they must appear in
#// P2's ground arena, P2's deck drops 10 → 8, and P1's deck and arena must be completely untouched.

## GIVEN
CommonSetup: ggw/ggw/{theirResources:7}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: SOR_104
WithP2Deck: [SOR_095 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095,SOR_095

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENACOUNT:0
P2DECKCOUNT:8
P2DISCARDCOUNT:1
P1DECKCOUNT:10
P1DISCARDCOUNT:0

---

# SimulateRequestBoundary_SearchConstraintsSurviveTheAnswer
#// SOR_104 U-Wing Reinforcement — REQUEST-BOUNDARY axis. The search prompt ends the request, so in
#// production the answer is resolved by a FRESH process: the ten peeked IDs, the "units only" filter and
#// the "≤3 picks, combined cost ≤7" constraint all have to be rebuilt from serialized state rather than
#// from anything the casting process still held. The pick here is deliberately the one that needs ALL of
#// them — four cost-1 Vanguard Infantry offered, three taken (the count cap), which a lost constraint
#// would let through as four, and a lost filter or peek list would drop to zero. Mirrors
#// CountCapOfTHREE_EvenWhenTheBudgetAllowsMore with the boundary inserted before the answer.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_108 SOR_108 SOR_108 SOR_108 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_108,SOR_108,SOR_108,SOR_108

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:7
P1DISCARDCOUNT:1
