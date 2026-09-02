# Ruling1_ShyyyoIsTheFirstUnitButDiscountsNobodyIncludingHimself
#// HMW_145 Origin Tree Shyyyo — Unit, Ground, cost 6, 4/8, [Command], trait Creature, non-unique.
#// Text: "Restore 1
#//        While you control a Kashyyyk base, the first, second, and third units you play each round cost
#//        [1 resource] less, [2 resources] less, and [3 resources] less, respectively."
#// COVERAGE: offer=N/A — a continuous cost modifier raises no decision and has no target pool. What
#//           would be the "offer" here is the PRICE, which every section asserts through resources ·
#//           decline=N/A (nothing optional) ·
#//           boundary=the tier ladder itself: TiersEscalateOneTwoThree (1st/2nd/3rd) paired with
#//           FourthUnitOfTheRoundGetsNoDiscount (the ladder ends) and
#//           DiscountResetsOnTheNextRound (the ladder restarts) ·
#//           control=N/A — "units YOU play" and "you control a Kashyyyk base" are both self-scoped, so
#//           there is no owner-vs-controller split to exercise. The adjacent risk is the modifier
#//           leaking to the OPPONENT's plays, which OpponentPlaysGetNoDiscount covers ·
#//           reqboundary=RequestBoundary_TierCountSurvivesBetweenActions — the "units played this
#//           round" counter is written by one player ACTION and read by the next, which is exactly the
#//           no-decision form of this cell ·
#//           modes=2P only ("you control" / "units you play" are self-only in every format — the
#//           documented no-extra-sections case) ·
#//           MULTI-COPY: Shyyyo is NON-UNIQUE, so the copies stack — TwoShyyyos_TheDiscountStacksPerCOPY
#//           and ThreeShyyyos_LadderStacksToThreeSixNine (with OneShyyyo_TheSameBoardCostsEighteen as
#//           the control), plus ThreeShyyyos_ThirdRungIsExactlyMinusNine which pins the last rung from
#//           both sides. Added 2026-09-02 on a user question; every section before them seeded
#//           exactly ONE copy, so nothing tested the non-unique case at all.
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. All three interactions below are USER
#//   RULINGS given 2026-08-26 and are encoded verbatim.
#//
#// ★ USER RULING 1. On a 6-resource round a player plays Shyyyo himself. He COUNTS as the first unit
#//   played this round, but gets NO discount — a unit's passive does not apply until it is in play, and
#//   his own cost is computed while he is still in hand. That leaves the player broke but with the
#//   ladder advanced, so the SECOND unit (-2) and the THIRD (-3) are both free if they cost 2 and 3.
#//   Shyyyo 6 → 0 left; SOR_095 (cost 2) at -2 → free; IBH_008 (cost 3) at -3 → free.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: HMW_145
WithP1Hand: SOR_095
WithP1Hand: IBH_008

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_145
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:2:CARDID:IBH_008
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Ruling2_UnitBeforeShyyyo_OnlyTheThirdGetsADiscount
#// ★ USER RULING 2. A unit is played BEFORE Shyyyo. That first unit gets nothing (no Shyyyo in play),
#//   Shyyyo himself gets nothing (still in hand when his cost is computed) — but he is the SECOND unit
#//   played, so the next one is the THIRD and takes the full -3.
#//   SOR_095 costs 2 (8 → 6), Shyyyo costs 6 (6 → 0), IBH_008 costs 3 - 3 = free.
#// The resource assertion is what discriminates: if Shyyyo wrongly discounted HIMSELF as the second
#// unit he would cost 4 and two resources would survive; if the counter ignored him the third unit
#// would be priced as the second (-2), cost 1, and fail to play at all with 0 resources.

## GIVEN
CommonSetup: bgw/bgw/{myResources:8;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: HMW_145
WithP1Hand: IBH_008

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:HMW_145
P1GROUNDARENAUNIT:2:CARDID:IBH_008
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Ruling3_KelleranBeqChainsBothDiscounts
#// ★ USER RULING 3. Shyyyo is already in play. LOF_100 Kelleran Beq (cost 7) is the FIRST unit of the
#//   round, so he costs 6 and leaves 1 resource ready. His When Played then searches the top 7 and
#//   plays a unit "costing 3 resources less" — and that fetched unit is the SECOND unit played this
#//   round, so it ALSO takes Shyyyo's -2. Its ceiling is therefore SIX: 1 ready + 2 (Shyyyo) + 3 (Beq).
#//   ASH_131 (cost 6) is exactly at the ceiling: 6 - 2 - 3 = 1, paid with the last ready resource.
#// ⚠ THIS RULING EXPOSED A LATENT BUG IN KELLERAN BEQ. His search filter priced candidates as
#//   CardCost + aspect penalty - 3, which ignores every play-cost FIELD MODIFIER — so with Shyyyo out
#//   it offered only units up to cost 4 and the cost-6 unit this ruling says is legal was never on the
#//   menu. The filter now prices through SWUComputePlayCost, the same pipeline that charges the play.
#// TWI_066 (cost 7) sits in the same deck as the control: 7 - 2 - 3 = 2 > 1 ready, so it stays out of
#// the offer and answering with it is refused.

## GIVEN
CommonSetup: bgw/bgw/{myResources:7;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: LOF_100
WithP1GroundArena: HMW_145:1:0
WithP1Deck: [ASH_131 TWI_066 SOR_095 IBH_008 SOR_095 IBH_008 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ASH_131

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_145
P1GROUNDARENAUNIT:1:CARDID:LOF_100
P1GROUNDARENAUNIT:2:CARDID:ASH_131
P1RESAVAILABLE:0

---

# Ruling3b_TheOverCeilingUnitIsNotOffered
#// HMW_145 × LOF_100 — the control for Ruling3. Same board, but the deck's only unit is TWI_066
#// (cost 7), which prices to 7 - 2 (Shyyyo) - 3 (Beq) = 2 against 1 ready resource. It is over the
#// ceiling, so it must not be offered — and answering with it anyway is refused server-side, leaving
#// Beq alone in the arena with the resource unspent.

## GIVEN
CommonSetup: bgw/bgw/{myResources:7;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: LOF_100
WithP1GroundArena: HMW_145:1:0
WithP1Deck: [TWI_066 SOR_251 SOR_251 SOR_251 SOR_251 SOR_251 SOR_251 SOR_251]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TWI_066

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_145
P1GROUNDARENAUNIT:1:CARDID:LOF_100
P1RESAVAILABLE:1

---

# TiersEscalateOneTwoThree
#// HMW_145 — the ladder, measured one rung at a time with Shyyyo already in play. Three cost-3 units
#// (IBH_008) played in one round cost 2, then 1, then 0 — a total of 3 rather than 9.
#// Starting from 3 ready resources, all three land and nothing is left.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# FourthUnitOfTheRoundGetsNoDiscount
#// HMW_145 — the ladder ENDS at three. A fourth unit in the same round pays its printed cost.
#// Three cost-3 units cost 2+1+0 = 3; the fourth costs the full 3, so 6 resources are needed for four
#// units and none are left. With a fourth rung the fourth would have been free and 3 would remain.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# NoKashyyykBase_NoDiscount
#// HMW_145 — the printed gate. Shyyyo is in play but the base is an ordinary Vigilance base with no
#// Kashyyyk trait, so nothing is discounted: one cost-3 unit costs the full 3.
#// (Expected to pass before implementation — an absence guard — and it is what stops the discount from
#// being written as an unconditional Shyyyo aura.)

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# KashyyykBaseButNoShyyyo_NoDiscount
#// HMW_145 — the other half of the gate. The Kashyyyk base is there but Shyyyo is not in play, so the
#// ability does not exist: the cost-3 unit costs 3.
#// (Also an absence guard, green before implementation.)

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# EventsDoNotConsumeATier
#// HMW_145 — "the first, second, and third UNITS you play". An EVENT played in between must not
#// advance the ladder. P1 plays SOR_251 Confiscate (cost 1, neutral aspect so no penalty, and it
#// fizzles harmlessly with no upgrades in play), then a cost-3 unit which must still be the FIRST unit
#// and take only -1 → cost 2. Starting from 3: 1 for the event, 2 for the unit, 0 left.
#// If events counted, the unit would be the second (-2), cost 1, and one resource would survive.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:IBH_008
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# OpponentPlaysGetNoDiscount
#// HMW_145 — "units YOU play". P1 controls Shyyyo and the Kashyyyk base; P2 plays a cost-3 unit and
#// pays the full 3. A modifier missing its "source controller == the player paying" gate would hand
#// the opponent P1's discount, which is the shape this catches.
#// (Absence guard, green before implementation.)

## GIVEN
CommonSetup: bgw/bgw/{myBase:HMW_021;theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:IBH_008
P2RESAVAILABLE:0

---

# DiscountResetsOnTheNextRound
#// HMW_145 — "each ROUND". Three units are played to exhaust the ladder, the round is passed out, and
#// the first unit of the NEW round is discounted -1 again.
#// Round 1: three IBH_008 at 2+1+0 = 3 of the 5 resources. All five ready again in round 2, where the
#// fourth IBH_008 is the FIRST unit of the new round and costs 3-1 = 2, leaving THREE.
#// That trailing resource count is the discriminator: a ladder that failed to reset would price it as
#// a fourth rung — full cost 3 — and leave two.
#// ⚠ Under P1OnlyActions the OPPONENT holds the claimed initiative, so P2 LEADS the new round and P1's
#//   play is refused by the turn-player gate until P2 has acted. The chain therefore needs a trailing
#//   `P2>Pass` after both resource passes; without it the fourth unit simply never enters and it reads
#//   exactly like the discount failing to reset.
#// ⚠ Both players need a seeded deck: the regroup DRAWS, and an empty deck damages the base instead.
#//   P1's hand is 4 - 3 played + 2 drawn - 1 played = 2 at the end.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1RESAVAILABLE:3
P1HANDCOUNT:2

---

# CostIsFlooredAtZero
#// HMW_145 — a -3 on a cost-2 unit is 0, never negative. SOR_095 (cost 2) played as the THIRD unit of
#// the round costs nothing, and the two units before it cost 2 and 1, so 3 resources cover all three.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: SOR_095
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:3:CARDID:SOR_095
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# RequestBoundary_TierCountSurvivesBetweenActions
#// HMW_145 — the request-boundary cell in its no-decision form. The card raises no prompt, but the
#// "units played this round" counter is written by one player ACTION and read by the cost computation
#// of the NEXT one, and in production those are separate processes. A boundary is inserted between
#// each play: the ladder must still climb 2 → 1 → 0.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# PilotPlayedAsAnUpgrade_NoDiscountAndNoTierConsumed
#// HMW_145 — "the first, second, and third UNITS you play". A Piloting card played AS A PILOT is an
#// UPGRADE play (CR 17.c) even though its printed CardType is still "Unit", so it must neither take a
#// rung nor advance the ladder. The modifier tells the two apart by the host: SWUComputePlayCost is
#// called with a $host only on the attach path, and that argument is threaded down to the field
#// modifiers for exactly this reason.
#// ⚠ A PILOTING CARD HAS ITS OWN, SEPARATE PILOTING COST. JTL_057's unit cost is 1 but its piloting
#//   cost ($pilotingCostData) is 2, and the attach path charges the latter. Budgeting the fixture from
#//   the printed unit cost is off by one and reads exactly like the discount misfiring.
#// ⚠ JTL_057 also carries a When-Played-as-upgrade heal offer. It is DECLINED here: a pending decision
#//   blocks every action, so leaving it unanswered silently refuses the next PlayHand and looks like
#//   the second unit being unaffordable.
#// JTL_057 attaches to the friendly SOR_237 as a pilot for its FULL piloting cost of 2 — 4 ready → 2.
#// IBH_008 is then still the FIRST unit of the round at -1, costing 2 → 0 left.
#// Both halves are load-bearing in that final 0: a discounted pilot would leave 1, a pilot that
#// consumed the first rung would price IBH_008 at -2 and leave 1, and both together would leave 2.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: JTL_057
WithP1Hand: IBH_008
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_057
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:IBH_008
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# TwoShyyyos_TheDiscountStacksPerCOPY
#// HMW_145 is NON-UNIQUE, so a player can field several — and nothing on the card says "only once".
#// Each copy is its own continuous cost modifier, and the ordinal ("the first unit you play each
#// round") is a property of the PLAY, not of any one Shyyyo, so every copy agrees on which rung applies
#// and they all reduce it. Two Shyyyos therefore make the FIRST unit of the round cost 2 less, not 1.
#//
#// LAW_162 Beach Patrol AT-ACT is the fixture: cost 8, [Command], and its only text is the passive
#// Overwhelm — no entry trigger to perturb the play. Under this board every pip is covered, so its
#// effective cost is the printed 8.
#// 6 resources is the discriminating budget: at −2 it costs exactly 6 and lands with nothing left; a
#// non-stacking (−1) implementation prices it at 7, the play silently no-ops, and the card stays in
#// hand. HANDCOUNT and RESAVAILABLE fail in opposite directions, so neither can pass alone.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: LAW_162
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:LAW_162
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# ThreeShyyyos_LadderStacksToThreeSixNine
#// HMW_145 — the extreme case: THREE Shyyyos, the whole ladder walked in one round. Each rung is
#// multiplied by the number of copies, so the first, second and third units of the round cost 3, 6 and
#// 9 less respectively rather than 1, 2 and 3.
#// Three cost-8 AT-ACTs: 8−3 = 5, then 8−6 = 2, then 8−9 → 0 (floored; the ladder cannot pay you).
#// Total 7, so seven resources land all three with nothing left — and seven is EXACT, not an upper
#// bound: the assertions pin both that every card left hand and that every resource was spent.
#// The single-copy control below prices the identical board at 18, which is what gives this number
#// meaning; a non-stacking implementation would need those 18 and strand two cards in hand here.

## GIVEN
CommonSetup: bgw/bgw/{myResources:7;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: LAW_162
WithP1Hand: LAW_162
WithP1Hand: LAW_162
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:6
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# OneShyyyo_TheSameBoardCostsEighteen
#// HMW_145 — the CONTROL for the section above, and the only thing that makes its 7 a measurement
#// rather than a number. Identical board and identical plays with a SINGLE Shyyyo: 8−1 = 7, 8−2 = 6,
#// 8−3 = 5, total 18. The two sections differ in exactly one fixture line and must disagree by 11
#// resources; a "the discount does not stack" implementation would produce this 18 in both.

## GIVEN
CommonSetup: bgw/bgw/{myResources:18;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: LAW_162
WithP1Hand: LAW_162
WithP1Hand: LAW_162
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# ThreeShyyyos_ThirdRungIsExactlyMinusNine
#// HMW_145 — the third rung pinned EXACTLY, which the AT-ACT sections above cannot do: at cost 8 a −9
#// floors to 0 and the last point is unreadable, so those prove only "at least −8".
#// ASH_083 Summa-verminoth is the fixture that closes it — cost 12, single [Vigilance] (covered by the
#// Kashyyyk base HMW_021, so no aspect penalty), non-unique, and its only abilities are passive
#// Sentinel and an On Attack, so PLAYING it raises nothing.
#//
#// Two cost-3 units burn rungs one and two (3−3 = 0 and 3−6 → 0, both free), then the Verminoth takes
#// rung three: 12 − 9 = 3. Exactly 3 resources are provided, so the arithmetic is pinned from BOTH
#// sides and every neighbouring value fails:
#//   −8  → it costs 4, the play silently no-ops, the card is stranded in hand (HANDCOUNT/SPACEARENACOUNT);
#//   −10 → it costs 2 and a resource is left over (RESAVAILABLE);
#//   −3 (no stacking) → it costs 9, unaffordable, stranded.
#// It is also a SPACE unit, which incidentally proves the ladder counts "units you play" without an
#// arena restriction — every other section in this file plays ground units.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: ASH_083
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:ASH_083
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Starhawk_ThreeShyyyos_TheDiscountAppliesBEFORETheHalving
#// ⚠ HMW_145 × JTL_105 The Starhawk — the ORDER-OF-OPERATIONS extreme case, and the one place these two
#// cards can disagree loudly.
#// ★ USER RULING 2026-09-02, and the CR says it outright: COSTS ARE DETERMINED BEFORE THEY ARE PAID.
#// Playing a card is a numbered sequence — CR step 3 "Determine cost(s)" produces the MODIFIED cost
#// (printed, then increases incl. the aspect penalty, then decreases, floored at 0 by CR 3.b), and only
#// then does step 4 "Pay cost(s)" exhaust resources for it.
#// Shyyyo is a COST MODIFIER, so it belongs to step 3. Starhawk says "While PAYING costs, you pay half
#// as many resources, rounded up" — a step 4 effect. So Shyyyo settles first and Starhawk halves the
#// settled result, never the reverse. Engine-side that is SWUComputePlayCost then SWUApplyCostHalving.
#// This is the same trap already recorded against Starhawk once (its Smuggle cost was being halved
#// INSIDE the cost computation instead of after).
#//
#// Three Shyyyos and a Starhawk in play, three units played in one round:
#//   rung 1 — IBH_008 cost 3: 3 − 3 = 0, halved = 0
#//   rung 2 — IBH_008 cost 3: 3 − 6 → 0 (floored), halved = 0
#//   rung 3 — ASH_083 cost 12: 12 − 9 = 3, ceil(3/2) = 2
#// Total 2, and exactly 2 resources are provided so the number is pinned from both sides. Each wrong
#// answer leaves a DIFFERENT residue rather than failing to play:
#//   halve-then-discount → ceil(12/2) = 6, 6 − 9 → 0, and all three are free: 2 resources left over;
#//   rounded DOWN instead of up → floor(3/2) = 1: 1 resource left over;
#//   no stacking (rung 3 = −3) → 12 − 3 = 9, ceil = 5: unaffordable, ASH_083 stranded in hand.

## GIVEN
CommonSetup: bgw/bgw/{myResources:2;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: ASH_083
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:ASH_083
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Starhawk_DoesNotHalveItsOwnPlay_ButStillTakesARung
#// HMW_145 × JTL_105 — the mirror of Ruling 1 (Shyyyo is the first unit played but discounts nobody,
#// himself included). A passive does not apply until its card is IN PLAY, and Starhawk's own cost is
#// computed while it is still in hand — so it is discounted by the Shyyyos but NOT halved by itself.
#// Three Shyyyos out, Starhawk played as the FIRST unit of the round: 9 − 3 = 6, unhalved. Exactly 6
#// resources, so self-halving (which would charge 3) leaves 3 over and fails RESAVAILABLE.
#// P2 controls no units, so Starhawk's Ambush adds no entry trigger and there is nothing to answer.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: JTL_105
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_105
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Starhawk_OnceInPlay_HalvesTheNextRungAndTheLadderAdvancedPastIt
#// HMW_145 × JTL_105 — the end-to-end. Starhawk pays 6 as the first unit (see above), and by the time
#// the SECOND unit is played it IS in play, so that one gets both halves of the interaction: it takes
#// rung TWO (Starhawk consumed rung one — a unit is a unit) and is then halved.
#//   ASH_083 cost 12: 12 − 6 = 6, ceil(6/2) = 3.
#// Total 6 + 3 = 9. If Starhawk did not consume a rung, ASH_083 would take rung ONE instead: 12 − 3 = 9,
#// ceil = 5, total 11 — unaffordable here, so it would strand in hand.

## GIVEN
CommonSetup: bgw/bgw/{myResources:9;myBase:HMW_021}
P1OnlyActions: true
WithP1Hand: JTL_105
WithP1Hand: ASH_083
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_105
P1SPACEARENAUNIT:1:CARDID:ASH_083
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# VuutunPalaa_FullStack_CostsOneWithEverythingOn
#// HMW_145 × JTL_105 × SEC_122 — all three cost effects at once, and they land on BOTH sides of the CR
#// step boundary. SEC_122 Vuutun Palaa is the ideal third card because its own two clauses split across
#// it: "costs 1 resource less for each friendly Droid unit" is a step-3 DETERMINE modifier, while
#// "each friendly Droid unit may be exhausted to pay costs as if it were a resource" is step-4 PAY.
#//   step 3: 9 printed − 5 (five Battle Droids) − 3 (Shyyyo rung 1 × 3 copies) = 1
#//   step 4: Starhawk halves → ceil(1/2) = 1, paid by exhausting ONE Droid (0 ready resources)
#// A 9-cost Capital Ship for a single resource.
#//
#// ⚠ AND VUUTUN CANNOT PAY FOR ITSELF. Its Droid-payment clause is a passive, so it is not active until
#// Vuutun is IN PLAY — while you are playing it, it is still in hand and its Droids cannot be exhausted
#// for it. That is the THIRD card in this file to follow the same rule (Shyyyo does not discount himself,
#// Starhawk does not halve its own play), and it is why this section pays with a real resource and every
#// Droid is still READY afterwards. Discovered the hard way: written with 0 resources first, the play was
#// silently refused with no prompt at all.
#// Each contributor is still separately visible: drop the Shyyyos and it is 4; drop the Droids and it is
#// 6; both leave 1 resource unable to cover it, so the card strands in hand.

## GIVEN
CommonSetup: bgw/bgw/{myResources:1;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_122
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: JTL_105:1:0

## WHEN
#// No Droid-payment prompt appears at all: Vuutun is the card being played, so its own clause is not yet
#// active and the single resource covers the cost outright.
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_122
P1GROUNDARENACOUNT:8
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY

---

# VuutunPalaa_FloorAtZero_IsFreeAndBurnsNoDroid
#// HMW_145 × JTL_105 × SEC_122 — CR 3.b, "a card's cost cannot be modified below 0", reached by TWO
#// independent decreases stacking rather than one big one.
#//   IBH_008 takes rung 1: 3 − 3 = 0, free.
#//   Vuutun then takes rung 2: 9 − 5 (Droids) − 6 (rung 2 × 3 copies) = −2 → floored to 0, halved 0.
#// So the whole board is deployed for nothing. The load-bearing assertion is that ALL FIVE Droids are
#// still READY: a free card must not burn a payment source, and an implementation that let the cost go
#// negative and then "paid" it, or that exhausted a Droid for a zero cost, fails right here.

## GIVEN
CommonSetup: bgw/bgw/{myResources:0;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: SEC_122
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_122
P1GROUNDARENACOUNT:9
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY

---

# VuutunPalaa_TheDroidsThatDeterminedTheDiscountAlsoPayIt
#// HMW_145 × JTL_105 × SEC_122 — the whole pipeline on ONE play, with the payment drawn from the very
#// units that shaped the cost. Vuutun is already in play, so its step-4 clause is live.
#//   two cheap units burn rungs 1 and 2 (3 − 3 = 0 and 3 − 6 → 0, both free)
#//   ASH_083 takes rung 3: 12 − 9 = 3  →  Starhawk halves → 2  →  paid by exhausting TWO Droids
#// Nothing here is readable from resources (there are none), so the Droid ready/exhausted split IS the
#// measurement: exactly two of the five are spent. Under a wrong rung (−3) the cost would be 9 → halved
#// 5 → five Droids, or unaffordable; without halving it would be 3 → three Droids.
#// ⚠ Vuutun itself is NOT a Droid (Separatist/Vehicle/Capital Ship), so it never inflates its own count.

## GIVEN
CommonSetup: bgw/bgw/{myResources:0;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: ASH_083
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: JTL_105:1:0
WithP1SpaceArena: SEC_122:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
#// Only the THIRD play costs anything (the first two are free at rungs 1 and 2), so this is the single
#// Droid-payment prompt in the section: cost 2, so exactly two of the five ready Droids are exhausted.
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:2:CARDID:ASH_083
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY

---

# AspectPenaltyIsAppliedBEFORETheShyyyoDiscount
#// HMW_145 — CR 3.a, the ordering INSIDE the determine step: "start with the card's printed cost, then
#// apply any modifiers that INCREASE the cost (including the aspect penalty) BEFORE any modifiers that
#// DECREASE the cost." The floor at 0 (CR 3.b) is what makes the two orders observable — with an
#// over-large discount they diverge:
#//   correct  → (3 printed + 2 penalty) − 9 = −4 → floored to 0 → FREE
#//   inverted → (3 − 9) floored to 0, then + 2 penalty = 2 → unaffordable at zero resources
#// JTL_216 Contracted Hunter is a vanilla single-[Cunning] 3-cost unit; the board covers Command,
#// Heroism and Vigilance only, so it carries a +2 penalty. Two cheap on-aspect units burn rungs 1 and 2
#// first. No Starhawk here on purpose — halving is a step-4 effect and would only blur a step-3 test.

## GIVEN
CommonSetup: bgw/bgw/{myResources:0;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: JTL_216
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:6
P1GROUNDARENAUNIT:5:CARDID:JTL_216
P1HANDCOUNT:0
P1RESAVAILABLE:0
