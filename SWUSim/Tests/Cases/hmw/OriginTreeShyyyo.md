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
#//           documented no-extra-sections case).
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
