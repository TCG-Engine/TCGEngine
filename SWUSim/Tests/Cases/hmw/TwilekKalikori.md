# TwilekHost_SearchesTopEightAndPlaysWithinBudget
#// HMW_265 Twi'lek Kalikori — Upgrade, cost 4, +2/+2, [Heroism], trait Item, non-unique.
#// Text: "When Played: If attached unit is a Twi'lek, search the top 8 cards of your deck for any number
#//        of Twi'lek units with a combined costs 5 or less and play each of them for free."
#// COVERAGE: offer=NonTwilekPickIsRefused + TwilekLeaderPickIsRefused — the TOPDECKSEARCH decision is
#//           CardID-based, not mzID-based, so SELECTABLEEXACT cannot see it; the stronger form is used
#//           instead (answer ILLEGALLY and assert the refusal), once per conjunct of the filter
#//           ("is a Unit" and "is a Twi'lek"). The 8-card WINDOW is asserted behaviourally by the
#//           EighthCardIsFound / NinthCardIsOutsideTheWindow pair ·
#//           decline=ChooseNone_EverythingReturnsToTheDeck (the "any number" includes zero) ·
#//           boundary=CombinedCostExactlyFive_BothPlayed (4+1=5 fits) vs
#//           CombinedCostOverBudget_OverflowDropped (2+2+2=6 does not) — plus the deck-depth pair above ·
#//           control=EnemyTwilekHost_SearchesTheKalikoriControllersDeck — an upgrade may legally be
#//           played on an ENEMY unit (CR 2.e default, and this card prints no controller restriction),
#//           and "your deck" then means the player who PLAYED the Kalikori, not the host's controller.
#//           Both decks are asserted ·
#//           reqboundary=RequestBoundary_FilterAndBudgetSurvive — genuinely load-bearing here: the
#//           legal-ID list and the cost constraint are written by _topDeckSearchBegin BEFORE the
#//           TOPDECKSEARCH decision and read by the finalize BEHIND it ·
#//           modes=2P only (no player reference and no friendly/enemy wording — "your deck" and
#//           "attached unit" both resolve without naming an opponent, so all three formats share one
#//           code path).
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. Read from the CR plus the released
#//   "search top N and play for free within a combined-cost budget" family, which this card joins
#//   verbatim: SOR_087 Darth Vader, SOR_104 U-Wing Reinforcement, LAW_063 L3-37, ASH_110 Ackbar.
#//   Those all route through DoTopDeckPlay, which already enforces the filter and the budget
#//   server-side and plays each pick through the REAL play pipeline.
#//
#// Host is TWI_108 (Twi'lek, 2/3). Top 8 of a 9-card deck hold two cheap Twi'lek units (SEC_160 cost 1,
#// TS26_43 cost 1 — combined 2, well inside the budget of 5); both are played for free and the other six
#// peeked cards go to the bottom. The +2/+2 lands on the host: 2/3 → 4/5.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [SEC_160 TS26_43 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 HMW_088]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_160,TS26_43

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_108
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_265
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:SEC_160
P1GROUNDARENAUNIT:2:CARDID:TS26_43
P1RESAVAILABLE:0
P1DECKCOUNT:7
P1DECKTOPCARD:HMW_088

---

# EighthCardIsFound_WindowBoundary
#// HMW_265 — deck-depth boundary, inside edge. The ONLY Twi'lek unit in the deck sits at position 8,
#// the last card the search can see. It is found and played.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SEC_160 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_160

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_160
P1DECKCOUNT:8
P1DECKTOPCARD:SOR_069

---

# NinthCardIsOutsideTheWindow_NotFound
#// HMW_265 — deck-depth boundary, outside edge, and the sharpest form available. Same board as
#// EighthCardIsFound_WindowBoundary but the only Twi'lek unit sits one card deeper, at position 9.
#// P1 then ANSWERS THE SEARCH WITH IT ANYWAY: SEC_160 passes every part of the filter (Twi'lek, Unit,
#// cost 1) and would be played under a 9-deep search — the ONLY thing that refuses it is that it was
#// never peeked, so it is not in the stored legal-ID list. Nothing is played and the eight peeked cards
#// are RETURNED to the bottom rather than milled (the ASH_224 shape).
#// ⚠ The search still PROMPTS with zero legal matches — the player is shown the top 8 either way — so
#//   the blank/illegal answer is required; this is shared behaviour across the whole DoTopDeckPlay
#//   family (cf. ash/AdmiralAckbar_AssumeAttackCoordinates::SelfDefeat_TakeNothing), not something
#//   this card introduces.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069 SEC_160]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_160

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_108
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:9
P1DECKTOPCARD:SEC_160
P1NODECISION

---

# CombinedCostOverBudget_OverflowDropped
#// HMW_265 — cost boundary, over the line. Three cost-2 Twi'lek units are picked: 2 + 2 fits inside the
#// budget of 5, the third would make 6 and is DROPPED (it joins the unpicked cards on the bottom of the
#// deck). "Combined cost 5 or less" is a server-side constraint, not a client hint.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: SEC_160:1:0
WithP1Deck: [TWI_108 TWI_108 TWI_108 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TWI_108,TWI_108,TWI_108

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_160
P1GROUNDARENAUNIT:1:CARDID:TWI_108
P1GROUNDARENAUNIT:2:CARDID:TWI_108
P1DECKCOUNT:7

---

# CombinedCostExactlyFive_BothPlayed
#// HMW_265 — cost boundary, on the line. "5 or LESS", so a 4-cost plus a 1-cost Twi'lek is legal and
#// BOTH are played. Also the free-play proof: P1 starts with 6 resources, pays 4 for the Kalikori and
#// nothing at all for the two fetched units, so exactly 2 remain.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [HMW_088 SEC_160 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HMW_088,SEC_160

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:HMW_088
P1GROUNDARENAUNIT:2:CARDID:SEC_160
P1RESAVAILABLE:2
P1DECKCOUNT:7

---

# NonTwilekPickIsRefused
#// HMW_265 — the FILTER's trait conjunct, tested the strong way: answer with a card the offer must not
#// have contained. SOR_095 Battlefield Marine is a unit, is inside the window, and costs 2 (so budget
#// is not what stops it) — but it is a Rebel/Trooper, not a Twi'lek. It must NOT be played; it falls
#// through to the bottom of the deck with the rest.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: SEC_160:1:0
WithP1Deck: [SOR_095 TWI_108 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,TWI_108

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_160
P1GROUNDARENAUNIT:1:CARDID:TWI_108
P1DECKCOUNT:8

---

# TwilekLeaderPickIsRefused
#// HMW_265 — the FILTER's OTHER conjunct: "Twi'lek UNITS". LAW_009 Hera Syndulla is a Twi'lek but its
#// CardType is Leader, so it must be refused even though the trait matches.
#// ⚠ FIXTURE CHOICE IS LOAD-BEARING HERE. The corpus has exactly three Twi'lek non-units, all leaders:
#//   SOR_008 (cost 6), HMW_013 (cost 6) and LAW_009 (cost 5). The two 6-cost ones are ALREADY dropped
#//   by the combined-cost budget, so a section built on either is green whether or not the type
#//   conjunct exists — it silently tests the budget instead. LAW_009 sits at exactly 5, inside the
#//   budget, so the type check is the only thing that can refuse it. (Verified: with the "=== 'Unit'"
#//   conjunct removed, this section reds; with HMW_013 as the fixture it did not.)
#// Answer order matters too: LAW_009 would eat the whole budget of 5, so under a trait-only filter
#// TWI_108 would ALSO be dropped — which is why index 1's identity is asserted, not just the count.
#// A leader seeded into a deck is artificial, but it is the only shape that isolates this branch.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: SEC_160:1:0
WithP1Deck: [LAW_009 TWI_108 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_009,TWI_108

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_160
P1GROUNDARENAUNIT:1:CARDID:TWI_108
P1DECKCOUNT:8

---

# NonTwilekHost_NoSearchButStatsStillApply
#// HMW_265 — the GATE's negative, and the load-bearing one. "If attached unit is a Twi'lek" governs the
#// ABILITY only: on a non-Twi'lek host (SOR_095, a Rebel Trooper) there is no search and no prompt, the
#// deck is untouched — but the printed +2/+2 is not an ability and still applies, 3/3 → 5/5.
#// Attaching to a non-Twi'lek is legal; the card prints no attach restriction.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_160 TS26_43 TWI_108 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 HMW_088]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_265
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1DECKCOUNT:9
P1DECKTOPCARD:SEC_160
P1NODECISION

---

# EnemyTwilekHost_SearchesTheKalikoriControllersDeck
#// HMW_265 — ownership vs control. The card prints no attach restriction, so under CR 2.e it may be
#// played on ANY unit including an enemy one; the only Twi'lek on the board here belongs to P2, so the
#// Kalikori auto-attaches there and buffs the OPPONENT's unit (2/3 → 4/5). But "search the top 8 cards
#// of YOUR deck" and "play each of them" belong to the player who PLAYED the upgrade — so P1 searches
#// P1's deck and the fetched unit lands in P1's arena. BOTH decks are asserted: a controller-scoped
#// misreading would search P2's.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP2GroundArena: TWI_108:1:0
WithP1Deck: [SEC_160 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069]
WithP2Deck: [TS26_43 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_160

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_108
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_265
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_160
P1DECKCOUNT:8
P2DECKCOUNT:9
P2DECKTOPCARD:TS26_43

---

# ChooseNone_EverythingReturnsToTheDeck
#// HMW_265 — "ANY NUMBER" includes zero. The search is offered (two legal Twi'lek units are in the
#// window) and P1 declines everything with a blank answer; nothing is played, and all eight peeked
#// cards go back to the bottom rather than being milled.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [SEC_160 TS26_43 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 HMW_088]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:9
P1DECKTOPCARD:HMW_088
P1NODECISION

---

# FetchedUnitIsREALLYPlayed_ItsOwnWhenPlayedFires
#// HMW_265 — "PLAY each of them" is a real play, not a put-into-play, so the fetched unit's own When
#// Played resolves. LAW_058 Fighting Back is a cost-2 Twi'lek whose When Played reads "Deal 1 damage to
#// a base"; P1 fetches it and aims the damage at the enemy base. A bare AddGroundArena implementation
#// would seat the unit and fire nothing.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: TWI_108:1:0
WithP1Deck: [LAW_058 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046 SOR_120 SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_058
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LAW_058
P2BASEDMG:1
P1DECKCOUNT:8

---

# RequestBoundary_FilterAndBudgetSurvive
#// HMW_265 — the request-boundary cell, and a real one rather than a formality. _topDeckSearchBegin
#// writes the legal-ID list AND the combined-cost constraint before queueing the TOPDECKSEARCH
#// decision, and the finalize behind that decision reads both. In production the answer arrives in a
#// fresh process, so anything held in memory would be gone and every pick would sail through
#// unfiltered and unbudgeted. Identical to CombinedCostOverBudget_OverflowDropped with one
#// SimulateRequestBoundary inserted before the answer: the third pick must still be dropped.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_265
WithP1GroundArena: SEC_160:1:0
WithP1Deck: [TWI_108 TWI_108 TWI_108 SOR_095 SOR_046 SOR_120 SOR_069 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:TWI_108,TWI_108,TWI_108

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_160
P1GROUNDARENAUNIT:1:CARDID:TWI_108
P1GROUNDARENAUNIT:2:CARDID:TWI_108
P1DECKCOUNT:7
