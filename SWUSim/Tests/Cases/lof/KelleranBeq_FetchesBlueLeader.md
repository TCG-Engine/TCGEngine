# TheLine_9Resources_FetchBlueLeaderFree_ThenPay2ToMoveIt
#// COVERAGE: offer=TheSearchOfferPricesBlueLeaderAtZERO (the fetch pool) + Fetched_MoveOffer_NOTOffered
#//           WhenOneShort (the pay-2 offer's affordability gate)
#//           decline=Fetched_DeclineTheMove_StaysInSpace
#//           boundary=9 vs 8 resources — Fetched_MoveOffer_NOTOfferedWhenOneShort is the other half
#//           control=Fetched_BothTriggersOrder_* (the two orders must disagree, and they do)
#//           reqboundary=Fetched_RequestBoundary_TheMoveOfferSURVIVES
#//           modes=2P only (no player reference in either card; "an enemy unit" is Ambush's own wording
#//           and is already covered by JTL_096's own file)
#//
#// THE REPORTED LINE, end to end, on an rgw board (SOR_026 Catacombs of Cadera + SOR_012 Leia Organa):
#//   9 resources → play LOF_100 Kelleran Beq for 7 → his When Played searches the top 7 and plays
#//   JTL_096 Blue Leader for FREE (cost 3, −3) → Blue Leader's own When Played offers "pay 2 resources"
#//   → pay the last 2 → it moves to the ground arena with 2 Experience tokens.
#//
#// ⚠ EVERY NUMBER HERE IS EXACT, WHICH IS THE POINT. Leia is Command+Heroism and BOTH cards are
#// Command+Heroism, so neither pays an aspect penalty (the Aggression base is irrelevant — the leader
#// already covers both pips). 9 − 7 = 2, Blue Leader costs 0, the move costs exactly the 2 that are
#// left, and the player ends on zero. One resource either way and the line does not exist, which is why
#// the 8-resource negative below is a real boundary and not decoration.
#//
#// ⚠ AND IT IS AN ALTERNATE PLAY PATH. Blue Leader is not played from hand here — Kelleran puts it on
#// top of the deck and plays it through SWUPlayTopDeckCard. Its When Played, its Ambush and the ORDER
#// between them all have to survive that path; every existing JTL_096 section plays it from hand, so
#// nothing in that file can see a regression here.
#//
#// This section is the clean case: the enemy board is EMPTY, so Ambush has no target and never fires,
#// leaving exactly one entry trigger and one YES to give.

## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>AnswerDecision:YES

## EXPECT
#// Kelleran (7/7) landed first, Blue Leader joined him on the GROUND as a 5/5 (3/3 + 2 Experience) …
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_100
P1GROUNDARENAUNIT:1:CARDID:JTL_096
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
#// … and nothing is left in space.
P1SPACEARENACOUNT:0
#// 9 − 7 (Kelleran) − 0 (Blue Leader) − 2 (the move) = 0.
P1RESAVAILABLE:0
#// 7 searched, 1 played, 6 to the bottom.
P1DECKCOUNT:6

---

# TheSearchOfferPricesBlueLeaderAtZERO
#// THE FETCH POOL, asserted rather than answered. Kelleran's filter prices each candidate through the
#// same pipeline that will charge it, so with 2 resources left after him Blue Leader (3 − 3 = 0) must be
#// offered — and SOR_119 Reinforcement Walker (cost 8, Command, covered → 8 − 3 = 5) must not be, because
#// 5 > 2. Answering a target proves the branch and says nothing about the pool.
#// ⚠ The pay-2 move is NOT part of this price: it is a separate optional cost on the fetched card's own
#// ability, so a filter that tried to reserve for it would wrongly exclude Blue Leader here.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_119 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:JTL_096
P1SEARCHPLAYABLENOT:SOR_119

---

# Fetched_MoveOffer_NOTOfferedWhenOneShort
#// THE BOUNDARY, and the half that proves the offer is gated on live payment capacity rather than being
#// unconditional. Identical line on 8 resources: Kelleran still costs 7, Blue Leader is still free, but
#// only 1 resource remains and the move costs 2 — so NO offer is raised at all (not an offer that then
#// fizzles), and Blue Leader stays in the SPACE arena as a plain 3/3 with no Experience.
## GIVEN
CommonSetup: rgw/rrk/{myResources:8;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:1

---

# Fetched_DeclineTheMove_StaysInSpace
#// THE DECLINE. "You may pay 2 resources" — refusing must leave Blue Leader in space as a 3/3 with no
#// Experience and the 2 resources unspent, not half-apply the clause.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>AnswerDecision:NO
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2

---

# Fetched_BothTriggersOrder_WhenPlayedFIRST_AmbushThenHitsGROUND
#// THE INTERACTION THAT ACTUALLY MATTERS. Blue Leader carries TWO play-time triggers — Ambush and the
#// When Played — so per CR 7.6.9 their controller orders them, and on THIS card the order changes which
#// ARENA the Ambush attack lands in. That ordering has to be offered on the fetched path too.
#//
#// Enemy board is deliberately one unit in EACH arena, so the two orders are distinguishable in every
#// observable rather than just in a prompt:
#//   • SOR_237 Alliance X-Wing (2/3) in space — its presence is what makes Ambush a live trigger at
#//     collection time, while Blue Leader is still a space unit;
#//   • SOR_095 Battlefield Marine (3/3) on the ground — the target it can only reach after moving.
#//
#// HERE: When Played first. Blue Leader pays 2, becomes a 5/5 ground unit, and Ambush then re-resolves
#// the MOVED unit and attacks the GROUND marine: 5 damage defeats it, 3 comes back. The X-Wing is
#// untouched. (Regression cover for the stale-mzID fizzle — the trigger must follow the unit's UID
#// across the arena change, not the mzID it was collected under.)
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
#// Moved, upgraded, and it attacked on the GROUND.
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_096
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:DAMAGE:3
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
#// The space unit never came under attack — that is the half that fails if the order was ignored.
P2SPACEARENACOUNT:1
P1RESAVAILABLE:0

---

# Fetched_BothTriggersOrder_AmbushFIRST_AttacksInSPACE_ThenMoves
#// THE OTHER ORDER, on the SAME board — and it must disagree with the section above in both arenas.
#// Ambush first: Blue Leader is still a 3/3 SPACE unit, so it attacks the X-Wing (2/3) — 3 damage
#// defeats it, 2 comes back. THEN the When Played pays 2 and moves it to the ground as a 5/5 still
#// carrying those 2 damage. The ground marine survives untouched.
#// Read the two sections together: X-Wing dead + marine alive here, marine dead + X-Wing alive above.
#// A build that quietly fixed the order would fail exactly one of the pair.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_096
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:DAMAGE:2
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# Fetched_RequestBoundary_TheMoveOfferSURVIVES
#// THE REQUEST-BOUNDARY CELL. The fetched play and the answer to its pay-2 offer are two separate
#// requests in production: Kelleran's search resolves, Blue Leader is played and its offer queued, and
#// only then does the player answer. Anything the fetch path held in an in-memory global — the unit's
#// UID on the trigger above all — is gone by the time the YES lands, and the move would silently do
#// nothing while every section above stayed green.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_096
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
P1SPACEARENACOUNT:0
P1RESAVAILABLE:0
