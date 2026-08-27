# SmugglePlay_StealsResource
#// COVERAGE: offer=StealOffer_EveryEnemyResourceIsOffered (both enemy resources pending, SELECTABLEEXACT)
#//           decline=N/A ("Take control of an enemy resource" is mandatory — no "you may")
#//           control=StolenReadyResource_ReturnsToOwnerStillReady + StolenExhaustedResource_ReturnsToOwnerStillExhausted
#//           (owner never changes; only control moves, and it moves back on leave-play)
#//           boundary=the ready/exhausted pair StolenReadyResource_* vs StolenExhaustedResource_*, plus the
#//           source pair PlayedFromHand_NoSteal / PlayedFromDiscard_NoSteal vs the Smuggle sections
#//           reqboundary=StolenResource_RevertsWhenDJLeaves + StolenReadyResource_ReturnsToOwnerStillReady
#//           (the steal's link survives a full action boundary — P2's attack — before the return fires)
#// SHD_213 DJ (3-cost 3/5, Smuggle 7 [Cunning Cunning]) — "When played using Smuggle: Take control
#// of an enemy resource." P1 smuggles DJ (both Cunning pips covered by base y + leader yw) and picks
#// one of P2's two exhausted resources: P1 ends with 9 resources (7 + deck replacement + stolen),
#// P2 with 1.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P1RESCOUNT:9
P2RESCOUNT:1

---

# StolenResource_RevertsWhenDJLeaves
#// SHD_213 DJ — "When this unit leaves play, that resource's owner takes control of it." After the
#// steal, P2's AT-AT Suppressor (SOR_039, 8/8) defeats DJ (3/5): the lazy leave-play sweep returns
#// the stolen resource to P2. P1 back to 8 resources, P2 back to 2.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:8
P2RESCOUNT:2

---

# StealOffer_EveryEnemyResourceIsOffered
#// THE OFFER AXIS. "An enemy resource" carries no ready/exhausted or cost qualifier, so the pool is every
#// non-removed resource the opponent controls — here both of P2's. Two legal picks keep the decision
#// interactive, so the offer is read while it is still PENDING and nothing resolves it. A pool narrowed to
#// only READY resources (or only exhausted ones) would be visible here as a one-entry list.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirResources-0&theirResources-1

---

# SmuggleSteal_ReadyResourceStaysReadyAndPaysForTheNextPlay
#// A stolen resource keeps the ready/exhausted state it had under its owner, and while DJ holds it the
#// THIEF may spend it. P1 has 6 ready resources plus DJ; Smuggle 7 is paid as DJ's own slot (CR 8.22.e)
#// plus all 6 others, and the deck replacement enters exhausted — so after the play P1's OWN pool is
#// completely dry. Stealing one of P2's two READY resources is therefore the only ready resource P1 has,
#// and it alone pays for the 1-cost JTL_196 played next. If the steal flipped the resource to exhausted
#// (or if a stolen resource were unspendable by the thief) that second play could not happen at all.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Hand: JTL_196
WithP1Resources: 6:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6
- P1>AnswerDecision:theirResources-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P1GROUNDARENAUNIT:1:CARDID:JTL_196
P1RESCOUNT:8
P1RESAVAILABLE:0
P2RESCOUNT:1
P2RESAVAILABLE:1

---

# StolenReadyResource_ReturnsToOwnerStillReady
#// The return leg preserves the resource's state too. P2's resources are READY, P1 steals one and does NOT
#// spend it, then P2's AT-AT Suppressor (SOR_039, 8/8) defeats DJ (3/5). The resource goes back to P2 STILL
#// READY — P2RESAVAILABLE returns to 2, not 1. Pair with StolenExhaustedResource_ReturnsToOwnerStillExhausted:
#// a return that reset the state would break exactly one of the two.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 6:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:1
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:6
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:0
P2RESCOUNT:2
P2RESAVAILABLE:2

---

# StolenExhaustedResource_ReturnsToOwnerStillExhausted
#// The other half of the state-preservation pair: with every enemy resource EXHAUSTED, the only thing DJ
#// can take is an exhausted one, it stays exhausted in P1's zone (P1RESAVAILABLE stays 0 even though a
#// fresh resource would normally arrive ready), and it goes home exhausted when DJ is defeated.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 6:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:6
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:0
P2RESCOUNT:2
P2RESAVAILABLE:0

---

# PlayedFromHand_NoSteal
#// "When played USING SMUGGLE" — the ability is bound to the Smuggle play, not to DJ entering play. Played
#// from hand for his printed cost of 3 he is just a 3/5: no decision is raised and P2 keeps all 3 resources
#// ready. An implementation that hung the steal on a plain When Played would fire here.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Hand: SHD_213
WithP1Resources: 5:SOR_046:1
WithP2Resources: 3:SEC_080:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P1RESCOUNT:5
P1RESAVAILABLE:2
P2RESCOUNT:3
P2RESAVAILABLE:3
P1NODECISION

---

# PlayedFromDiscard_NoSteal
#// The same negative on the third play path. SHD_053 Second Chance is attached to a seated DJ; P2's AT-AT
#// Suppressor defeats him, which stamps the free-replay marker on the DJ card in P1's discard (SHD_053
#// lands at discard index 0, DJ at index 1). Replaying DJ from the discard pile still does not steal —
#// P2 keeps 3 ready resources and no decision is pending.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1GroundArena: SHD_213:1:0
WithP1GroundArenaUpgrade: 0:SHD_053
WithP2GroundArena: SOR_039:1:0
WithP1Resources: 2:SOR_046:1
WithP2Resources: 3:SEC_080:1

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P1RESCOUNT:2
P1RESAVAILABLE:2
P2RESCOUNT:3
P2RESAVAILABLE:3
P1NODECISION

---

# SmuggleViaTechGrant_StealsAndLeavesEnemyResourcesUntouched
#// DJ's steal fires for ANY Smuggle play, including one bought with a GRANTED Smuggle cost. SHD_248 Tech
#// makes each friendly resource Smuggle for "printed cost + 2 + its aspect icons", i.e. 3 + 2 = 5 for DJ
#// (Cunning covered) against his printed Smuggle of 7. P1 has only 4 ready resources besides DJ, so the
#// printed 7 is unaffordable and the play can only have gone through on the granted 5 — DJ's own slot
#// pays 1 (CR 8.22.e) and the 4 others cover the rest.
#// The point of the section is the ENEMY side: P2 also controls a Tech, so every one of P2's resources
#// has Smuggle too, and paying for P1's Smuggle must not reach across and exhaust them. P2 ends with 2
#// resources, both still READY — only the one DJ took has moved, and it arrives ready under P1.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_248
WithP2GroundArena: SHD_248
WithP1Resources: 4:SOR_046:1,1:SHD_213:1
WithP2Resources: 3:SEC_080:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:4
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_213
P1RESCOUNT:6
P1RESAVAILABLE:1
P2RESCOUNT:2
P2RESAVAILABLE:2

---

# TwinSuns_TakesTheResourceFromTHATSEAT
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "Take control of AN ENEMY RESOURCE."
#// Pins that the resource is taken from the SEAT THE PICK NAMED: it comes from seat 4, whose count drops
#// to 1, and DJ enters under P1. That path is driven by $lastDecision and works at any seat count.
#//
#// ⚠ HONEST LIMIT — this does NOT pin the $opp fix in SHD_213#0. That value is only consulted as a
#// fallback when the chosen resource's Owner field is unset ("resource Owner is often 0"), and a harness
#// fixture always sets it. Measured: swapping $opp back to OtherPlayer() leaves this section GREEN.
#// The fallback is a real correctness fix (it decides who gets the resource back when DJ leaves play),
#// it is simply not reachable from here. Do not read this section as covering it.
## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP4Resources: 2:SEC_080:0
WithP1Deck: SOR_095
## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:p4Resources-0
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P4RESCOUNT:1
