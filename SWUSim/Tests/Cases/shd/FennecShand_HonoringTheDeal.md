# FennecShand_Deployed_PlayAmbush
#// SHD_016 Fennec Shand (deployed Action) — same play-≤4 + Ambush grant. Deployed (5 resources), the
#// deployed Action plays SOR_229 (cost 2) at index 1 with Ambush (no enemy units → Ambush attack skipped).

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# FennecShand_Front_PlayAmbush
#// SHD_016 Fennec Shand (front Action [1 resource, Exhaust]) — "Play a unit that costs 4 or less from
#// your hand (paying its cost). Give it Ambush for this phase." SOR_229 (cost 2) is played and gains
#// Ambush; with no enemy units the Ambush attack is skipped, so it sits in play with the keyword.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 6

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# Front_OfferExcludesOverCostAndUnaffordable
#// SHD_016 (front) — "Play a unit that costs 4 or less from your hand (PAYING ITS COST)". Two independent
#// filters gate the pool and this section separates them with a discriminating hand:
#//   myHand-0 SOR_119 Reinforcement Walker — printed cost 8, over the "4 or less" cap → never offered.
#//   myHand-1 SOR_239 Rebel Pathfinder     — cost 2, affordable → offered.
#//   myHand-2 SOR_237 Alliance X-Wing      — cost 2, affordable → offered.
#//   myHand-3 JTL_115 Clone Combat Squadron— printed cost 4 (passes the cap) but UNAFFORDABLE here.
#// Fennec's front Action costs [1 resource, Exhaust], so 4 resources leave only 3 to pay with and the
#// cost-4 unit drops out. The pairing section below flips exactly that one input (5 resources) and the
#// cost-4 unit reappears — so the exclusion here is proven to be affordability, not the cost cap.
#// The decision is deliberately left PENDING: an OFFER can only be asserted before it is consumed.
#// Aspects: Echo Base (Command) + Fennec (Cunning/Heroism) covers all four hand cards — no +2 penalty.
#// COVERAGE: offer=this section + Deployed_ActionChargesNoResourceSoTheCostFourUnitIsAffordable (pending
#//           SELECTABLEEXACT over the hand pool, one per leader side)
#//           boundary=this section vs Front_OfferIncludesTheCostFourUnitWhenAffordable (4 vs 5 resources
#//           flips the cost-4 unit in and out of the pool) and Front_AmbushGrantExpiresWhenThePhaseEnds
#//           vs the two HASKEYWORD sections above it (in-phase vs after-phase)
#//           reqboundary=Front_AmbushAttacksAnEnemyUnitImmediately (the play, the Ambush yes/no and the
#//           attack target are three separate requests, and the attack resolves against the state
#//           serialized after each one)
#//           control=N/A — the ability plays a unit out of its own controller's hand onto that
#//           controller's side; there is no seat the effect can cross and no control-taking clause
#//           decline=NOT COVERED — the target pool as currently built has no choose-nothing option on
#//           either side, so there is no decline branch to encode; flagged for the engine owner

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 JTL_115]
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-1&myHand-2
P1HANDCOUNT:4

---

# Front_OfferIncludesTheCostFourUnitWhenAffordable
#// Boundary partner of the section above: same hand, one more resource. 5 − 1 (the Action's resource
#// cost) = 4, so JTL_115 (cost 4) is now payable and joins the pool. SOR_119 (cost 8) is STILL absent —
#// the "4 or less" cap is a printed-cost cap that no amount of resources can buy past.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 JTL_115]
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-1&myHand-2&myHand-3
P1HANDCOUNT:4

---

# Front_PaysBothCostsAndExhaustsTheLeader
#// The front Action's price tag, which no existing section measured: [1 resource, Exhaust] PLUS the
#// chosen unit's own cost. 5 resources − 1 (ability) − 2 (SOR_239 Rebel Pathfinder) = 2 ready, and
#// Fennec is left EXHAUSTED. Contrast with the deployed section further down, whose Action text carries
#// no cost at all and leaves all 4 resources available to pay for the unit.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 JTL_115]
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1

## EXPECT
P1LEADER:EXHAUSTED
P1RESCOUNT:5
P1RESAVAILABLE:2
P1HANDCOUNT:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_239
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# Front_AmbushAttacksAnEnemyUnitImmediately
#// The granted Ambush is the half neither pre-existing section could reach: both of them played into an
#// EMPTY enemy board, so the keyword was only ever asserted as a sticker. Here P2 has units, so the
#// Ambush window actually opens and the freshly played unit readies and attacks.
#// SOR_239 Rebel Pathfinder is 2/3; SOR_176 ISB Agent is 1/3 → the Agent takes 2, the Pathfinder takes 1
#// back and ends EXHAUSTED from the attack. Two enemy units are seated so the Ambush target is a real
#// choice rather than an auto-resolve.
#// Per CR: Ambush is NOT a "When Played" ability, so this is the keyword's own window.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 JTL_115]
WithP1Resources: 5
WithP2GroundArena: SOR_176:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_239
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_176
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# Deployed_ActionChargesNoResourceSoTheCostFourUnitIsAffordable
#// The deployed side's Action drops the front's "[1 resource, Exhaust]" price entirely — it reads only
#// "Action: Play a unit that costs 4 or less from your hand (paying its cost)."
#// This is the sharp contrast with Front_OfferExcludesOverCostAndUnaffordable: IDENTICAL hand, IDENTICAL
#// 4 resources, but here the full 4 are available to pay the unit's cost, so JTL_115 (cost 4) is in the
#// pool. On the front side the Action skimmed one resource first and JTL_115 fell out. Nothing else in
#// the fixture differs, so the pool difference isolates the missing resource cost.
#// SOR_119 (cost 8) stays out on both sides — the "4 or less" cap is unaffected by the deploy state.
#// A deployed leader seats at the END of the ground arena; it is the only unit here, so myGroundArena-0.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 JTL_115]
WithP1Resources: 4

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SHD_016
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1SELECTABLEEXACT:myHand-1&myHand-2&myHand-3
P1HANDCOUNT:4
P1RESAVAILABLE:4

---

# Deployed_NoPlayableUnitInHand_NoOfferAndNothingIsSpent
#// The fizzle branch: the only card in hand is SOR_119 Reinforcement Walker at cost 8, over the "4 or
#// less" cap, so the ability has nothing to offer. Resources are deliberately ample (8, enough to pay
#// for the Walker outright) to prove the pool is gated on the PRINTED cost cap and not on affordability.
#// Nothing is prompted, nothing leaves hand, and no resource is spent.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_119
WithP1Resources: 8

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1HANDCARD:0:SOR_119
P1GROUNDARENACOUNT:1
P1RESCOUNT:8
P1RESAVAILABLE:8

---

# Front_AmbushGrantExpiresWhenThePhaseEnds
#// "Give it Ambush for this phase" — the grant is a PHASE effect, and every other section here only ever
#// looks at it inside the phase that created it. This is the expiry boundary: the unit is still on the
#// board after the action phase ends, but the keyword is gone.
#// Two cards are seeded in hand so the pick is a real choice rather than an auto-resolve, and both decks
#// are seeded so the regroup draw step does not empty a deck (an empty deck damages the base instead).

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_239 SOR_237]
WithP1Resources: 5
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_239
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# Deployed_ActionCostsNoExhaustAndRepeats
#// SHD_016 (deployed) — the deployed Action is a BARE "Action:" with no [Exhaust] and no resource cost,
#// unlike the front's [1 resource, Exhaust]. So it is repeatable within one turn: Fennec plays two units
#// back to back, paying only the units' own costs (8 -> 6 -> 2), and stays READY throughout. This is the
#// contrast that makes the front's once-per-turn limit meaningful.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_119 SOR_239 SOR_237 SHD_100]
WithP1Resources: 8

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-1
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-2

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:2

---

# Front_ChooseNothing_NothingIsPlayedAndTheCostIsStillPaid
#// SHD_016 (front) — USER RULING (2026-08-15): "play a unit from your HAND" is always DECLINABLE, since
#// the hand is a HIDDEN zone and a player is never forced to reveal that they held a playable unit. P1
#// activates the front Action with affordable units in hand and then declines: nothing is played and no
#// Ambush is granted. The ACTIVATION cost is still paid in full — the [1 resource, Exhaust] is the price
#// of using the ability, not of the effect resolving — so Fennec ends exhausted on 4 ready resources.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046]
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1LEADER:EXHAUSTED
P1RESAVAILABLE:4

---

# Front_ChooseNothing_NothingIsPlayedAndTheCostIsStillPaid_ByConfirmingEmpty
#// ⚠ PASS-TWIN of Front_ChooseNothing_NothingIsPlayedAndTheCostIsStillPaid — byte-for-byte identical except the decline.
#// `-` and "PASS" are two DIFFERENT declines, and the client only ever submits "PASS" (all three decline
#// paths in Core/UILibraries*.js). Historically every decline test here answered `-`, so the path players
#// actually take was untested. This continuation (SHD_016#play) is one that does more than apply the pick, and
#// it now runs on a decline because SWUQueueMayChooseTarget defaults dontSkipOnPass to 1 — this twin is
#// what covers that. If the two declines ever diverge, one of the pair goes red.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046]
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1LEADER:EXHAUSTED
P1RESAVAILABLE:4
