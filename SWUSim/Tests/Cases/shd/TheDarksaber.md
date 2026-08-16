# AspectWaiver_Mandalorian
#// SHD_126 The Darksaber (Upgrade, cost 4, Command, +4/+3) — "While playing this upgrade on a Mandalorian
#// unit, ignore its aspect penalty." P1's base is off-Command (Aggression), so SHD_126 would normally cost
#// 4 + 2 penalty = 6; attaching to the Mandalorian SOR_142 waives the penalty, so it costs exactly 4 (all
#// of P1's resources) and SOR_142 becomes a 6-power unit wearing the Darksaber.
#// COVERAGE: offer=AttachOffer_EnemyMandalorianIsALegalHost + BothHostsOffered_WhenTheAspectPenaltyIs
#//           Affordable (both left PENDING and read with P1SELECTABLEEXACT), with the negative half of the
#//           pool asserted through auto-resolution in AffordabilityIsPerHost_UnpayableNonMandalorianIsNot
#//           EvenOffered (one legal host ⇒ P1NODECISION) ·
#//           boundary=AffordabilityIsPerHost_UnpayableNonMandalorianIsNotEvenOffered vs BothHostsOffered_
#//           WhenTheAspectPenaltyIsAffordable (4 vs 6 resources, the only difference), plus AspectWaiver_
#//           Mandalorian vs NonMandalorianHost_AspectPenaltyPaidInFull (waived 4 vs paid 6) and
#//           TwoReductionsOnDifferentHosts_NeitherHostIsAffordable (per-target affordability at 3) ·
#//           control=EnemyMandalorianHost_PenaltyWaivedAndUpgradeLandsOnTheEnemy (P1's Darksaber attached
#//           to a unit P2 controls) + EnemyHost_GrantedOnAttackResolvesForTheHostsController (the granted
#//           On Attack resolves "friendly" from the HOST's seat, CR 2.e) ·
#//           reqboundary=RequestBoundary_HostPickSurvivesSerialization (the cost depends on the host, so
#//           the pending pick has to survive the serialize/deserialize round trip) ·
#//           decline=N/A — no clause on this card is a "you may": the attach host pick is a mandatory
#//           target choice (no "choose nothing" answer), the aspect waiver is a static cost replacement,
#//           and the granted On Attack hands out Experience to every eligible unit with no opt-out.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_142:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:POWER:6

---

# NonMandalorian_NoWaiver
#// SHD_126 The Darksaber — the waiver is host-conditional. Attaching to a NON-Mandalorian unit (SOR_046)
#// keeps the +2 off-Command penalty, so the cost is 6; with only 4 resources the play fails and nothing
#// attaches (SOR_046 stays a 3-power unit, resources untouched).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:POWER:3

---

# OnAttack_MandalorianExp
#// SHD_126 The Darksaber — the attached unit gains "On Attack: give an Experience token to each OTHER
#// friendly Mandalorian unit." SHD_034 (Mandalorian, wearing the Darksaber) attacks an enemy unit; the
#// other friendly Mandalorian SOR_142 (2 power) gains an Experience token → 3 power. (The host is excluded.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_034:1:0
WithP1GroundArenaUpgrade: 0:SHD_126
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3

---

# NonVehicleRestriction_OnlyVehicle_NoAttach
#// SHD_126 The Darksaber — "Attach to a non-Vehicle unit." With ONLY a Vehicle in play (AT-ST), there is no
#// legal host, so the upgrade cannot be attached — the Vehicle gains nothing and the Darksaber stays in hand.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# AttachOffer_EnemyMandalorianIsALegalHost
#// THE OFFER AXIS. "Attach to a non-Vehicle unit" names no controller, so an ENEMY non-Vehicle unit is a
#// legal host (CR 2.e) — and the Mandalorian aspect-penalty waiver reads the chosen HOST's traits, not the
#// host's controller, so an enemy Mandalorian waives the penalty exactly like a friendly one. P1's base and
#// leader are both Aggression, so [Command] is uncovered and the Darksaber would cost 4+2=6; with only 4
#// resources it is affordable ONLY where the waiver applies. Both boards hold a Mandalorian (P1's SHD_040
#// Clan Wren Rescuer, P2's SHD_056 Follower of The Way), so BOTH survive the affordability filter and the
#// pick stays interactive — the decision is deliberately left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SHD_040:1:0
WithP2GroundArena: SHD_056:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# EnemyMandalorianHost_PenaltyWaivedAndUpgradeLandsOnTheEnemy
#// THE CONTROL AXIS + the resolution half of AttachOffer_EnemyMandalorianIsALegalHost. Same board; P1 picks
#// P2's SHD_056 Follower of The Way. The waiver applies (Mandalorian host), so all 4 resources cover the
#// unwaived-6 upgrade exactly and none are left. The Darksaber physically sits on the enemy unit: SHD_056 is
#// 1/3, gains +4/+3 from the Darksaber and a further +1/+1 from its own "while this unit is upgraded" clause
#// → 6/7. P1's own Mandalorian is untouched, which is the proof the host pick was honoured.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SHD_040:1:0
WithP2GroundArena: SHD_056:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AffordabilityIsPerHost_UnpayableNonMandalorianIsNotEvenOffered
#// The waiver is host-conditional, so AFFORDABILITY is host-conditional too: the same card in hand costs 4
#// on the Mandalorian SHD_040 and 6 on the non-Mandalorian SHD_029 Pyke Sentinel. With exactly 4 resources
#// only the Mandalorian is payable, so the Pyke Sentinel must be filtered out of the host pool rather than
#// offered and then failing at payment. That leaves ONE legal host, so the pick auto-resolves with no
#// decision at all — P1NODECISION plus the Pyke ending bare is what proves it was never in the pool.
#// The boundary partner is BothHostsOffered_WhenTheAspectPenaltyIsAffordable (same board, 6 resources).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: [SHD_040:1:0 SHD_029:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_040
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:CARDID:SHD_029
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# BothHostsOffered_WhenTheAspectPenaltyIsAffordable
#// Boundary partner of AffordabilityIsPerHost_UnpayableNonMandalorianIsNotEvenOffered — identical board,
#// two more resources. At 6 the unwaived cost is payable, so the non-Mandalorian Pyke Sentinel re-enters
#// the host pool and both units are offered. The single point of difference between the two sections is
#// the resource count, which is what makes the pair a boundary; the decision is left PENDING to read the
#// offer. Waiving the penalty is a DISCOUNT, never a restriction — the Mandalorian stays legal either way.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_126
WithP1GroundArena: [SHD_040:1:0 SHD_029:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NonMandalorianHost_AspectPenaltyPaidInFull
#// The resolution half of BothHostsOffered_WhenTheAspectPenaltyIsAffordable and the cost-side twin of
#// AspectWaiver_Mandalorian: choosing the NON-Mandalorian host means paying the [Command] penalty, so the
#// Darksaber costs 4+2=6 and all six resources are spent. SHD_029 Pyke Sentinel is 2/3 and ends 6/6 wearing
#// the Darksaber. Visible-resource arithmetic is the whole point of the pair — waived is 4, paid is 6.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_126
WithP1GroundArena: [SHD_040:1:0 SHD_029:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SHD_029
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# MandalorianVehicleGivesNoWaiver_NothingIsPlayable
#// The waiver keys off the HOST, and a Mandalorian that can never BE the host cannot supply it. P1 controls
#// SHD_042 Concord Dawn Interceptors — a Mandalorian, but a Vehicle, so "attach to a non-Vehicle unit"
#// removes it from the pool before any cost is computed — and SHD_029 Pyke Sentinel, a legal host that is
#// not Mandalorian and therefore costs the full 4+2=6. With 4 resources there is no payable host at all, so
#// the play is a silent no-op: the Darksaber stays in hand, nothing is spent and no decision is raised.
#// A "does the player control a Mandalorian?" reading of the waiver would wrongly discount to 4 and attach.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SHD_029:1:0
WithP1SpaceArena: SHD_042:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# TwoReductionsOnDifferentHosts_NeitherHostIsAffordable
#// AFFORDABILITY MUST BE EVALUATED PER TARGET, never as "the best reduction available anywhere". Two
#// different reductions are live, each on a DIFFERENT unit: SOR_061 Guardian of the Whills discounts the
#// first upgrade played ON IT by 1 but is not Mandalorian, so the Darksaber costs 4+2-1 = 5 there; SHD_056
#// Follower of The Way is Mandalorian, so the penalty is waived and it costs 4 there — but it gets no
#// Guardian discount. With 3 resources NEITHER host is payable and the card is unplayable, even though
#// pooling the two reductions onto one imaginary target would read 4+2-1-2 = 3 and look affordable.
#// The play is a silent no-op: nothing attaches, nothing is spent, no decision is raised.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_126
WithP1GroundArena: [SOR_061:1:0 SHD_056:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1RESAVAILABLE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# OnAttack_ExperienceGoesToEveryOtherFriendlyMandalorian_AndNoOneElse
#// The full membership matrix for "each OTHER friendly Mandalorian unit", read off one attack. The host is
#// SOR_095 Battlefield Marine (3/3, NOT a Mandalorian) wearing the Darksaber, so it ends at 7/6 from the
#// +4/+3 alone — the "other" exclusion and the Mandalorian requirement both keep it off the Experience list.
#// Gets one: SHD_034 Supercommando Squad 4/4 → 5/5 and SHD_040 Clan Wren Rescuer 1/2 → 2/3 (friendly ground
#// Mandalorians), and SHD_042 Concord Dawn Interceptors 1/4 → 2/5 — the clause names no ARENA, so a friendly
#// Mandalorian in SPACE is included. Gets nothing: SHD_029 Pyke Sentinel 2/3 (friendly, not Mandalorian) and
#// P2's SHD_056 Follower of The Way 1/3 (Mandalorian, but ENEMY — "friendly" is the second exclusion).
#// The attack goes at P2's base for 7 rather than at a unit, so no combat damage muddies the stat reads.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SHD_034:1:0 SHD_040:1:0 SHD_029:1:0]
WithP1GroundArenaUpgrade: 0:SHD_126
WithP1SpaceArena: SHD_042:1:0
WithP2GroundArena: SHD_056:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:CARDID:SHD_034
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:2:CARDID:SHD_040
P1GROUNDARENAUNIT:2:POWER:2
P1GROUNDARENAUNIT:2:HP:3
P1GROUNDARENAUNIT:3:CARDID:SHD_029
P1GROUNDARENAUNIT:3:POWER:2
P1GROUNDARENAUNIT:3:HP:3
P1SPACEARENAUNIT:0:CARDID:SHD_042
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:CARDID:SHD_056
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:3

---

# EnemyHost_GrantedOnAttackResolvesForTheHostsController
#// THE CONTROL AXIS for the GRANTED ability (the attach half is
#// EnemyMandalorianHost_PenaltyWaivedAndUpgradeLandsOnTheEnemy). Per CR 2.e an upgrade's granted ability
#// belongs to the ATTACHED UNIT's controller, so "each other FRIENDLY Mandalorian unit" is resolved from
#// P2's seat even though P1 owns the Darksaber. P2's SHD_056 host (1/3, +4/+3 from the Darksaber and +1/+1
#// from its own upgraded clause → 6/7) attacks P1's base for 6; P2's OTHER Mandalorian SHD_040 gets the
#// Experience token (1/2 → 2/3) while P1's Mandalorian SHD_034 gets nothing (4/4). A handler that read
#// "friendly" from the upgrade's owner instead of the host's controller would invert both of those.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP2GroundArena: [SHD_056:1:0 SHD_040:1:0]
WithP2GroundArenaUpgrade: 0:SHD_126
WithP1GroundArena: SHD_034:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:6
P2GROUNDARENAUNIT:0:CARDID:SHD_056
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:1:CARDID:SHD_040
P2GROUNDARENAUNIT:1:POWER:2
P2GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:0:CARDID:SHD_034
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# RequestBoundary_HostPickSurvivesSerialization
#// In production the host pick and the payment straddle two HTTP requests: the offer is built and shown in
#// one, the answer arrives in the next. The whole point of this card is that the COST depends on which host
#// was picked, so the pending decision has to be read back out of the serialized gamestate rather than out
#// of anything the first request left in memory. Same board and 6 resources as
#// NonMandalorianHost_AspectPenaltyPaidInFull, with the boundary inserted between the play and the answer:
#// the non-Mandalorian host must still charge the full 4+2=6 afterwards.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_126
WithP1GroundArena: [SHD_040:1:0 SHD_029:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SHD_029
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
