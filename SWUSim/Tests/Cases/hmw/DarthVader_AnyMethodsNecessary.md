# WhenPlayed_PlaysTwoUnitsForFreeAndDealsTwoToEach
#// HMW_043 Darth Vader, Any Methods Necessary — Unit (Ground) 9/8, cost 9, [Aggression][Command][Villainy],
#// Force/Imperial/Sith, unique.
#// "Saboteur
#//  When Played: Search the top 8 cards of your deck for up to 2 units that each cost 4 or less, play them
#//  for free, and deal 2 damage to each of them."
#// Saboteur needs no code — HMW_043 is already in $Saboteur_Cards and the keyword has generic coverage
#// under Tests/Cases/keywords/. Everything below is the When Played half.
#// `rgk` = Aggression base + Command/Villainy leader, so all THREE of Vader's aspects are covered and he
#// costs exactly 9 (an off-aspect fixture would cost 11+ and the play would silently no-op).
#// COVERAGE: offer=IllegalPick_UnitCostingFIVE_IsRefused + IllegalPick_NonUnitEvent_IsRefused (asserted as a
#//           SERVER-side refusal, which is stronger than the client offer list — the search decision
#//           resolves picks against every peeked card, so the filter must hold in the handler)
#//           decline=UpToTwo_DeclineAll_NothingPlayedNoDamage · boundary=IllegalPick_UnitCostingFIVE_IsRefused
#//           (cost 4 plays in the positive, cost 5 refused) + Top8Depth pair
#//           control=N/A (no owner-scoped zone; "your deck" is read by the controller who is also the owner —
#//           HMW_043's When Played fires on entry, before any control change is possible)
#//           reqboundary=SurvivesTheRequestBoundary (the played-unit UIDs are written before the search
#//           decision and read by the damage step behind it)

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_095 SOR_046 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_046

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:3

---

# IllegalPick_UnitCostingFIVE_IsRefused
#// "units that each cost 4 or less" is a PER-CARD cap, not a combined budget. The boundary partner to the
#// positive above (which plays a cost-4 SOR_046): IBH_076 Rampaging Wampa costs 5 and must not be playable
#// even though it is the only other unit in the top 8. Answering with it must leave it unplayed.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_095 IBH_076 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,IBH_076

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# IllegalPick_NonUnitEvent_IsRefused
#// "for up to 2 UNITS" — an event in the top 8 is not a legal pick. SOR_171 Mission Briefing is a cost-3
#// event, i.e. it passes the cost gate and is refused purely on type. Without this the filter could be
#// reading cost alone and every other section would still pass.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_171

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043

---

# Top8Depth_NinthCardIsNotReachable
#// "the top 8 cards" — a legal unit sitting NINTH is out of reach. Its partner is the positive section,
#// where the same card is reachable inside the top 8; without the pair the depth could be any number.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043

---

# PlayedForFree_EvenWhenOffAspectAndUnaffordable
#// "play them for FREE" — the fetched units cost 0, including their aspect penalty. Vader costs 9 of the
#// 9 available resources, so a SOR_046 (Vigilance/Heroism, off-aspect here: 4 + 4 = 8) is unaffordable by
#// any normal measure and must still land. 9 resources in, 0 left, and the unit is in play.

## GIVEN
CommonSetup: rgk/rgk/{myResources:9}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_046 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:2
P1RESAVAILABLE:0

---

# TwoDamage_DefeatsAOneHpFetchedUnit
#// The damage is real and lands on the unit that was just played: SOR_128 Death Star Stormtrooper is
#// 3/1, so 2 damage defeats it immediately. It reaches the discard, and only Vader is left in the arena.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_128 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1DISCARDCOUNT:1

---

# TwoDamage_HitsONLYTheUnitsPlayedThisWay
#// Scope exclusion: "each of THEM" is the units played by this ability, not every friendly unit and not
#// Vader himself. A pre-existing SOR_095 on the board must end undamaged, and so must Vader.
#// Enforced by IDENTITY (the UniqueIDs of what was played), not by "everything that entered recently" —
#// the arena already holds a same-named unit here, which is exactly what a name- or slot-based
#// implementation would confuse.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_043
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:DAMAGE:2

---

# UpToTwo_PickingOnlyOne
#// "up to 2" — one pick is legal even with two legal candidates present. The unpicked SOR_046 is NOT
#// played and goes to the bottom of the deck with the rest (deck count is unchanged at 8: 8 peeked,
#// 1 played, 7 returned... plus nothing drawn, so 7 remain).

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_095 SOR_046 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1DECKCOUNT:7

---

# UpToTwo_DeclineAll_NothingPlayedNoDamage
#// "up to 2" includes ZERO. Declining is a real branch: nothing is played, nothing is damaged, and the
#// whole peeked set returns to the deck (count unchanged at 8) rather than being milled.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_095 SOR_046 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1DECKCOUNT:8

---

# NoLegalUnitInTheTop8_CleanFizzle
#// No valid pick must be a clean no-op: no crash, no dangling decision, and the peeked cards are NOT
#// milled. Vader himself still enters play — the search rider is not a condition on his own arrival.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1DECKCOUNT:8
P1NODECISION

---

# EmptyDeck_CleanNoOp
#// The other end of the same axis: an EMPTY deck must not crash or leave a decision pending, and Vader
#// still arrives. Distinct from the section above, where the deck is full of ineligible cards.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1NODECISION

---

# SurvivesTheRequestBoundary
#// The set of units played this way is written BEFORE the search decision and read by the damage step
#// BEHIND it. An in-memory global is empty in the next request, so the damage would silently not happen
#// in a real game while passing in a single-process harness. Driving a boundary between the play and the
#// answer is what makes that failure visible.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_095 SOR_046 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_095,SOR_046

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:DAMAGE:2

---

# FetchedUnitsWhenPlayedFires_AfterTheWholeAbility
#// "play them for free" is a REAL play: the fetched unit's own When Played resolves — AFTER this whole
#// ability, damage rider included (USER RULING 2026-08-13, strict CR 522.e/7.6.8). SHD_080 Salacious
#// Crumb (cost 1, 1/3, mandatory "heal 1 from your base") proves both halves: he takes the rider 2
#// first and SURVIVES at 2 damage, then his heal fires and the pre-damaged base ends at 4. Under the
#// old put-into-play placement the base stayed at 5 (no trigger at all).

## GIVEN
CommonSetup: rgk/rgk/{myResources:12;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_080
P1GROUNDARENAUNIT:1:DAMAGE:2
P1BASEDMG:4

---

# TwoPicks_InteractiveWhenPlayedPromptComesAFTERTheDamage
#// The ordering cell under the strict ruling: two picks, the first with an INTERACTIVE When Played.
#// Both units play, both take the rider 2 INLINE — and only then does JTL_051 Red Squadron X-Wing's
#// "you may deal 2 damage to this unit. If you do, draw a card" prompt surface. Answering YES lands
#// 2 + 2 = 4 = its full HP, so its own ability finishes it (space empty, discard 1, the draw still
#// happens — "if you do" was satisfied) while SOR_095 sits at its rider 2 and lives.
#// Deck: 8 seeded − 2 played = 6, then the YES draw takes 1 → 5; the drawn card is the hand's content.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [JTL_051 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_051,SOR_095
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1
P1DECKCOUNT:5

---

# FetchedSHIELDEDUnit_TakesTheTwoFIRST_ThenGetsItsShield
#// ⚠ RULING PIN (user, 2026-08-13 — REVERSING the same-day per-play ruling after weighing the CR):
#// the fetched units' entry triggers resolve only after this WHOLE ability, damage included — CR 522.e
#// ("abilities that trigger while playing the card resolve only after the current ability finishes
#// resolving") + the 7.6 never-interrupts sentence. So a fetched Shielded unit takes the rider 2 on its
#// BARE face and the shield arrives afterwards: SOR_064 Wilderness Fighter (2/4, keyword-only Shielded)
#// ends at 2 damage WITH an intact shield. The per-play reading (shield first, rider absorbed: 0 damage,
#// shield popped) is exactly what this section forbids.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_064 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_064

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_064
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# FetchedTwoHPShieldedUnit_DiesBeforeItsShieldArrives
#// The boundary partner that makes the ruling sharp: SOR_207 Crafty Smuggler is 2/2, so the rider
#// DEFEATS it before its Shielded ever resolves. CR 780: the trigger still fires for a unit the action
#// defeated — and then fizzles cleanly, because there is no unit to shield. Arena holds only Vader,
#// the Smuggler is in the discard, and no decision dangles.

## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SOR_207 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_207

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1DISCARDCOUNT:1
P1NODECISION
