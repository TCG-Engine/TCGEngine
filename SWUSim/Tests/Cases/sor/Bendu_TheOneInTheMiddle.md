# DiscountAppliesOnlyOnce
#// SOR_056 Bendu — the discount is a ONE-SHOT "next card" charge, consumed by the first neutral card.
#// Bendu attacks (arms), then P1 plays two JTL_069 (Vigilance neutral, cost 5): the FIRST costs 3, the
#// SECOND costs the full 5. 8 ready resources → 3 + 5 = 0 left, both played. (If the charge weren't
#// consumed, both would cost 3 and leave 2 — RESAVAILABLE:0 is the consume discriminator.)
#// COVERAGE: offer=Sentinel_KeywordAndAttackPoolNarrowsToBendu (the only pool this card touches is the
#//           enemy ATTACK-TARGET pool, read directly as a count — exactly 1 — with a non-Sentinel ally
#//           and the base both excluded; the On Attack charge itself raises no decision) ·
#//           decline=N/A (neither clause is optional: Sentinel is a printed static keyword and the
#//           On Attack charge is armed with no "you may" — it is a cost reduction, never a prompt) ·
#//           control=StolenBendu_ChargeGoesToTheNewController (owner ≠ controller: the "next card YOU
#//           play" charge is read from the attacking unit's CONTROLLER, proved in both directions in
#//           one section) ·
#//           boundary pair=OnAttack_NextNeutralCardCheaper (cost 5 → 3) vs
#//           DiscountFloorsAtZero_AndIsStillConsumed (cost 1 → 0, never negative, charge still spent),
#//           plus the aspect pair NoDiscountForVillainyCard / NoDiscountForHeroismCard against the
#//           neutral positives, and the duration pair DiscountAppliesOnlyOnce (one card, same phase) vs
#//           DiscountExpiresAtEndOfPhase (unspent charge is gone after the regroup) ·
#//           reqboundary=SimulateRequestBoundary_DiscountChargeSurvives (the charge is armed by one
#//           serialized action and consumed by a later one, so it must round-trip through the gamestate)

## GIVEN
CommonSetup: bbk/bbk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069
WithP1Hand: JTL_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:0

---

# NoDiscountForVillainyCard
#// SOR_056 Bendu — the discount excludes [Villainy] (and [Heroism]) cards. Bendu attacks (arms), then
#// P1 plays SOR_225 (a Villainy Space unit, cost 1) → NO discount → full cost 1, so 3 ready resources →
#// 2 left. (If the discount wrongly applied, SOR_225 would cost 0 → RESAVAILABLE:3.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:2

---

# OnAttack_NextNeutralCardCheaper
#// SOR_056 Bendu (Unit 4/7, Vigilance, Sentinel) — "On Attack: The next non-[Heroism], non-[Villainy]
#// card you play this phase costs 2 less." Bendu attacks the base (arming the discount), then P1 plays
#// JTL_069 (a Vigilance = neutral Space unit, cost 5) → it costs 3, so 7 ready resources → 4 left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:1
P1RESAVAILABLE:4

---

# SimulateRequestBoundary_DiscountChargeSurvives
#// SOR_056 Bendu — the "next non-Heroism/non-Villainy card costs 2 less this phase" charge is armed by the
#// attack and consumed by a LATER player action; in production each of those actions is its own request, so
#// the charge must be serialized. Mirrors DiscountAppliesOnlyOnce with a boundary after the attack and
#// after the first (discounted) play: first JTL_069 still costs 3, second still costs the full 5 → 0 left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069
WithP1Hand: JTL_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:0

---

# Sentinel_ForcesTheEnemyAttackOntoBendu
#// SOR_056 Bendu (4/7) — the card's FIRST clause, which no existing section touches. Bendu prints
#// Sentinel, so "units in this arena can't attack your non-Sentinel units or your base": P2's Consular
#// Security Force declares an attack on P1's base and is redirected onto Bendu. The base takes nothing,
#// P1's unprotected Death Star Stormtrooper standing beside Bendu is untouched, Bendu takes 3 and the
#// 3/7 attacker takes Bendu's 4 back.

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 1
WithP1GroundArena: [SOR_056:1:0 SOR_128:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# Sentinel_KeywordAndAttackPoolNarrowsToBendu
#// SOR_056 Bendu — the Sentinel clause read directly rather than inferred from where an attack landed.
#// Bendu carries the keyword, and the valid-attack-target count for P2's ground unit collapses to
#// exactly 1: P1's base and P1's non-Sentinel Stormtrooper are both out of the pool, leaving only
#// Bendu. Reading the count is what separates "the pool narrowed" from "the attack happened to land
#// there".

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 1
WithP1GroundArena: [SOR_056:1:0 SOR_128:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
ATTACKTARGETS:2:G:0:1

---

# NoDiscountForHeroismCard
#// SOR_056 Bendu — the discount excludes [Heroism] as well as [Villainy]; only the Villainy half of the
#// exclusion had a section. Under a Vigilance/Heroism leader (so the aspect is fully covered and the
#// printed cost is the whole story), Bendu attacks to arm the charge and P1 plays the Alliance X-Wing
#// (SOR_237, [Heroism], cost 2): it must cost the full 2, so 3 ready resources leave 1. A wrongly
#// applied discount would make it free and leave 3.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:1
P2BASEDMG:4

---

# DiscountFloorsAtZero_AndIsStillConsumed
#// SOR_056 Bendu — the boundary below the discount's own size. 2-1B Surgical Droid (SOR_059) is a
#// neutral [Vigilance] card costing 1, so "costs 2 less" can only take it to 0, never to a negative
#// refund — and the charge is spent all the same. P1 arms the discount, plays the Droid for 0, then
#// plays Distant Patroller (SOR_060, neutral, cost 2) at its FULL price: 3 ready resources − 0 − 2 = 1.
#// A negative-cost bug would show 2 or more left here; an unconsumed charge would show 3.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: SOR_059
WithP1Hand: SOR_060

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1RESAVAILABLE:1

---

# DiscountExpiresAtEndOfPhase
#// SOR_056 Bendu — "the next … card you play THIS PHASE". The charge is armed and then deliberately
#// left unspent: both players pass into the regroup, decline the optional resource, and the new action
#// phase opens with the charge gone. The same JTL_069 that costs 3 in OnAttack_NextNeutralCardCheaper
#// now costs its full 5, so 8 readied resources leave 3. This is the duration edge that a charge stored
#// without a phase scope would fail while passing every same-phase section above.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:3
P2BASEDMG:4

---

# StolenBendu_ChargeGoesToTheNewController
#// SOR_056 Bendu — the CONTROL axis. Bendu is OWNED by P1 but CONTROLLED by P2 (the end state after a
#// take-control effect), and P2 attacks with it. "The next … card YOU play" is read from the attacking
#// unit's CONTROLLER, so the charge must belong to P2 and to nobody else. Both seats then play the same
#// neutral JTL_069 (cost 5) out of eight ready resources: P1 pays the full 5 and is left with 3, P2 pays
#// the discounted 3 and is left with 5. An owner-keyed charge would give exactly the opposite pair.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;theirResources:8}
WithActivePlayer: 2
WithP2GroundArenaControlled: SOR_056:1
WithP1Hand: JTL_069
WithP2Hand: JTL_069

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1RESAVAILABLE:3
P2RESAVAILABLE:5
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:1
P1BASEDMG:4
