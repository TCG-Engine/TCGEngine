# PlaysAChosenUnitForFree
#// SHD_123 Bounty Hunter's Quarry — Upgrade, cost 1, [Command].
#// "Attached unit gains: 'Bounty - Search the top 5 cards of your deck, or 10 cards instead if this unit
#//  is unique, for a unit that costs 3 or less and play it for free.'"
#// A Bounty is collected by the OPPONENT of the bountied unit's controller, so P1 attaches it to P2's
#// unit and collects when P1 defeats it. SOR_046 (3/7) kills the 3/3 host and survives the counter.
#// SEC_237 Supreme Council Aide costs 1 and is played FREE — P1 spends nothing on it (2 resources in,
#// 2 still available after the attack).
#// COVERAGE: offer=Top5_NonUniqueHost_DeeperThanFiveIsNotFound + Top10_UniqueHost_DepthSevenIsFound
#//           decline=DeclineTheSearch_NothingPlayed · boundary=that same top-5-vs-top-10 pair, and the
#//           cost gate is pinned by CostFourUnitIsNotFound
#//           control=N/A (the bounty resolves for the collecting player and searches THEIR deck; owner
#//           and controller of that deck are the same player by construction)
#//           reqboundary=N/A (the search decision is raised and resolved inside one collection; no state
#//           is written before it and read after)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SEC_237 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_237

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_237
P1RESAVAILABLE:2

---

# Top5_NonUniqueHost_DeeperThanFiveIsNotFound
#// "the top 5 cards, or 10 instead if this unit is unique" — the host here is SOR_095 Battlefield Marine,
#// NON-unique, so only 5 cards are seen. SEC_237 sits SEVENTH behind six ineligible events, so it is out
#// of reach. Its partner below flips only the host's uniqueness and nothing else.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SEC_237 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SEARCHPLAYABLENOT:SEC_237

---

# Top10_UniqueHost_DepthSevenIsFound
#// The boundary partner: SOR_160 Wolffe is UNIQUE, so the search sees 10 cards and the same
#// seventh-from-top SEC_237 becomes reachable. Deck and everything else are identical to the section
#// above — only the host's uniqueness differs, which is what makes the pair prove the 5-vs-10 rule
#// rather than some other property of the fixture.
#// Wolffe is 3/2, so SOR_046's 3 damage still defeats him and the bounty still triggers.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SEC_237 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SEARCHPLAYABLEHAS:SEC_237

---

# CostFourUnitIsNotFound
#// "a unit that costs 3 or less" — the cost gate, with its boundary partner being the cost-1 SEC_237
#// played in the first section. SOR_046 Consular Security Force costs 4 and sits in the top 5, so only
#// the cost test can exclude it.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SOR_046 SEC_237 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SEARCHPLAYABLEHAS:SEC_237
P1SEARCHPLAYABLENOT:SOR_046

---

# DeclineTheSearch_NothingPlayed
#// The bounty is collected but the search is declined: nothing is played and the peeked cards go back to
#// the deck rather than being milled (5 seeded, 5 still there).

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SEC_237 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1DECKCOUNT:5

---

# BountyCollectedDuringTheREGROUPPhase
#// The bounty does not have to be collected on an attack. JTL_216 Contracted Hunter defeats ITSELF when
#// the regroup phase starts, so P1 collects the bounty — and runs the whole search-and-play — inside the
#// regroup phase. A collection path that only works mid-combat would fail here.
#// Decks are seeded on both sides because reaching regroup with an empty deck adds 6 damage per base and
#// would drown the assertion.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: JTL_216:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SEC_237 SOR_171 SOR_171 SOR_171 SOR_171]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_237

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_237
