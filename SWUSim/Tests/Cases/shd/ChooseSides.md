# ChooseSides_ExchangeControl
#// SHD_132 Choose Sides — "Choose a friendly non-leader unit and an enemy non-leader unit. Exchange
#// control of those units." P1 swaps its SOR_046 for P2's SHD_095: afterwards P1 controls SHD_095 and P2
#// controls SOR_046.
#// COVERAGE: offer=FriendlyOffer_ExcludesTheDeployedLeader + EnemyOffer_ExcludesTheDeployedLeader (the
#//           two picks have different pools and each excludes that side's deployed leader unit) ·
#//           control=ExchangeControl (both directions of the swap) +
#//           ExchangesControlButNotOwnership (control moves, ownership does not — proven by where the
#//           unit lands when it leaves play) · boundary pair=NoEnemyNonLeaderUnit_StillResolvesAndIsPaidFor
#//           and NoFriendlyNonLeaderUnit_StillResolvesAndIsPaidFor (each half of the two-target
#//           requirement empty in turn, against the both-halves-present section above) ·
#//           reqboundary=SimulateRequestBoundary_EnemyPickAfterTheFriendlyPick (the friendly unit chosen
#//           in the first request must still resolve after serialization AND after the arena shifts
#//           under the first half of the exchange) · decline=N/A (no "you may" on either pick; both are
#//           mandatory chooses, and with a pool on each side the effect always happens) ·
#//           NOTE: what happens to a swapped unit's UPGRADES is deliberately left unasserted here.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# ChooseSides_FriendlyOffer_ExcludesTheDeployedLeader
#// SHD_132 — "Choose a FRIENDLY NON-LEADER unit": the first pick must skip P1's deployed leader unit,
#// which sits at the END of the ground arena (deployed leaders seat last). Two ordinary friendly units
#// are seated so the pick stays interactive and the pool itself is what the end state exposes; the
#// decision is left PENDING with no answer.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ChooseSides_EnemyOffer_ExcludesTheDeployedLeader
#// SHD_132 — the mirror half: "an ENEMY NON-LEADER unit". P1 fields a single non-leader unit, so the
#// friendly pick auto-resolves onto it and the flow lands directly on the enemy pick, which is left
#// pending. P2's deployed leader unit (seated last, at theirGroundArena-2) must not be in the pool even
#// though it is an enemy unit in the arena.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# ChooseSides_NoEnemyNonLeaderUnit_StillResolvesAndIsPaidFor
#// SHD_132 — with a friendly unit but NO enemy non-leader unit (P2 fields only a deployed leader, which
#// the card cannot choose), the exchange has nothing to do. The event must still resolve: it is paid
#// for, it goes to P1's discard, no decision is left hanging, and P1's own unit stays P1's.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_132
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:1
P1NODECISION

---

# ChooseSides_NoFriendlyNonLeaderUnit_StillResolvesAndIsPaidFor
#// SHD_132 — the other empty half: P1 fields only a deployed leader, so there is no friendly non-leader
#// unit to give away. The enemy unit must stay under P2's control and the event must still be spent.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_132
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1NODECISION

---

# ChooseSides_ExchangesControlButNotOwnership
#// SHD_132 — the exchange moves CONTROL only; each unit keeps its original OWNER. The probe is where a
#// unit goes when it leaves play: P1 takes P2's Battlefield Marine (3/3, already at 2 damage so one hit
#// kills it) and immediately attacks the Wampa (SOR_164, 4/5) with it. The Marine dies and must land in
#// P2's discard — its owner's — even though P1 controlled it when it died. P1's discard holds only the
#// event. The two traded units are deliberately DIFFERENT cards so the discard assertion is unambiguous.
#// P1 fields one non-leader unit, so the friendly pick auto-resolves and only the enemy pick is answered.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_132

---

# SimulateRequestBoundary_EnemyPickAfterTheFriendlyPick
#// SHD_132 — the two picks arrive as separate requests in production, and the friendly unit chosen in
#// the first one is carried to the second only as an identifier: it must still resolve to the right
#// unit after a round-trip through the serialized gamestate, and after the caster's arena has shifted
#// under the first half of the exchange. Two units per side keep both picks interactive; the boundary
#// sits between them and the swap must come out exactly as it does without it.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:0:CARDID:SHD_095
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P1NODECISION

---

# TwinSuns_ExchangeGivesToTHATUnitsController
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "Exchange control of those units."
#// The seat that RECEIVES the friendly unit is the chosen ENEMY unit's controller — a determined seat,
#// known only once the pick lands. It was threaded in from the offer builder as OtherPlayer($player),
#// literally seat 2, so above two seats the caster took a SEAT 4 unit while SEAT 2 received the friendly
#// one: a three-way swap the card never describes. P2's arena must stay EMPTY — that is the assertion
#// that catches it.
## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 7
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP4GroundArena: SHD_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:p4GroundArena-0
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P4GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0
