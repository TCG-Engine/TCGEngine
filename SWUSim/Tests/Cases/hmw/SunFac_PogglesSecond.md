# WhenPlayed_GritScalesWithDamageOnTheUnit
#// COVERAGE: offer=Offer_EveryUnitIsSelectable_IncludingSelfEnemyAndADeployedLeader
#//           decline=N/A (printed text has no "may" and no "up to" — the choose is MANDATORY, so
#//                        SWUQueueChooseTarget is correct and there is no decline branch to test)
#//           boundary=N/A (no numeric threshold in the text; Grit's per-damage SCALING is pinned by the
#//                        0-damage vs 2-damage pair below, which is the quantity cell)
#//           control=TakeControl_GrittedUnitKeepsGritAcrossTheMove
#//           reqboundary=SimulateRequestBoundary_GrantStillLandsAfterTheBoundary
#//           modes=2P,TwinSuns (unqualified "a unit" is a WHOLE-TABLE pool — at 3+ seats it must reach
#//                 seats 3/4, not just the one opponent in view) · TeamSuns=N/A (no friendly/enemy wording)
#//
#// HMW_243 Sun Fac - Poggle's Second (Ground, Villainy, Separatist, unique, cost 2, 2/3)
#//   "When Played: Give a unit Grit for this phase. (It gets +1/+0 for each damage on it.)"
#//
#// ⚠ THE LOAD-BEARING NUMBER IS "PER DAMAGE", NOT "+1". SEC_080 Imperial Dark Trooper is a 3/3 seeded
#//   with TWO damage, so Grit must take it to power 5. A flat "+1" implementation lands on 4 and a
#//   "+1/+1" one moves the HP too — both are ruled out here and in the two sections below.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:CARDID:HMW_243
P1NODECISION

---

# WhenPlayed_UndamagedUnit_GainsGritButNoPowerYet
#// The ZERO case. "A unit" carries no "damaged" qualifier, so an undamaged unit is a perfectly legal
#// (if currently inert) target — the keyword lands and simply contributes nothing until damage does.
#// This is the other half of the quantity pair: it fails against any implementation that adds a flat
#// bonus rather than reading $obj->Damage.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:3

---

# GritAddsPowerOnly_RemainingHpIsUnchanged
#// Grit is +1/+0 PER DAMAGE — power only. The engine has been wrong about this before (the TS26 port
#// found Grit read as +1/+1), and a +1/+1 reading is invisible to every power assertion above.
#// SEC_080 is a 3/3 on two damage: power climbs to 5, HP stays at its printed 3, so the unit is still
#// one point from dying rather than four.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3

---

# GritIsRecomputed_DamageTakenAFTERTheGrantAlsoCounts
#// Grit is a CONTINUOUS read of the unit's current damage, not a bonus stamped at grant time. The
#// discriminating board is a target that is UNDAMAGED when it receives Grit (so a stamping
#// implementation banks +0) and is then damaged inside the same phase.
#//
#// LAW_124 Industrious Team (4/7, seeded — it carries a When Played, so it must never be PlayHand'd)
#// takes Grit at 0 damage, then attacks SOR_095 Battlefield Marine (3/3): it deals 4 and kills it,
#// and takes 3 counter-damage. Power must now read 4+3 = 7. A stamped grant leaves it at 4.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENACOUNT:0

---

# Offer_EveryUnitIsSelectable_IncludingSelfEnemyAndADeployedLeader
#// THE OFFER CELL. Answering a target proves the branch and says nothing about the pool, and this
#// card's pool is its whole rules content: "a unit" names no controller, no arena and no card type, so
#// it spans the entire table.
#//
#// Four distinct exclusions are ruled out at once:
#//   • Sun Fac HIMSELF — he is already in play when his own When Played resolves, so he is a legal
#//     target (the "a unit is often its own valid target" trap);
#//   • the friendly SEC_080;
#//   • an ENEMY unit in the other player's arena — an unqualified "a unit" reaches BOTH sides;
#//   • P2's DEPLOYED LEADER — there is no "non-leader" qualifier, and a deployed leader is only
#//     reachable through ZoneSearch's 'Leader Unit' type mapping;
#//   • and the enemy SPACE unit — no arena qualifier either.
#// The decision is deliberately left PENDING so the pool itself can be read.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# EnemyUnit_IsALegalTarget_AndReallyGetsGrit
#// The behavioural half of the unqualified-target family: the grant must actually land on the enemy
#// unit, not merely appear in the menu. An enemy SOR_095 on two damage goes to power 5.
#// (Handing an opponent Grit is a real, if unusual, play — the card offers no way to refuse it, which
#// is exactly why the pool must not be quietly narrowed to friendlies.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:2
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:HASKEYWORD:Grit
P2GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# SelfTarget_SunFacMayGritHimself
#// Sun Fac is in play by the time his own When Played resolves, so he is a legal target for it.
#// Asserted behaviourally as well as in the offer above, because "exclude the source" is the reflexive
#// thing to write for a When Played and nothing in this card's text asks for it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_243
P1GROUNDARENAUNIT:1:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# GritExpiresAtTheNextPhase_PowerBackToPrinted
#// THE DURATION ENDING — the cell that grant cards skip most often, and the one that a permanently
#// granted keyword passes every other section of this file with.
#//
#// P1 grits a damaged SEC_080, then ends the action phase (P2 auto-passes under P1OnlyActions) and
#// both players pass the regroup resource step. RegroupPhaseStart runs SWUExpireTurnEffects('phase'),
#// so the Grit token must be gone and power back to the printed 3 — with the damage still on the unit,
#// which is what makes the two readings differ.
#// ⚠ Both decks are seeded on purpose: an empty deck at regroup puts CR 6.1 damage on the base, which
#// has previously moved numbers a section was asserting.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1Hand: HMW_243
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:3

---

# SimulateRequestBoundary_GrantStillLandsAfterTheBoundary
#// THE REQUEST-BOUNDARY CELL. In production every interactive decision ENDS the request: the target
#// choose is queued in one process and answered in a fresh one, so anything the offer parked in an
#// in-memory global would be empty by the time the answer arrives, and the handler would return
#// silently — the card simply doing nothing, with a green suite.
#// Identical to the first section except for the boundary inserted between the offer and the answer.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5

---

# TakeControl_GrittedUnitKeepsGritAcrossTheMove
#// PERSISTENCE ACROSS A STATE TRANSITION. The grant lives in the unit's TurnEffects array, and moving
#// a unit between arenas is exactly where that array has been silently destroyed before (a generated
#// Add<Zone> accessor with no array branch stringified it to the literal "Array", wiping every turn
#// effect on the moved unit).
#// P1 grits an enemy SOR_095 on two damage, then plays SOR_224 Change of Heart to take control of that
#// same unit. It arrives in P1's arena still Gritted, still on two damage, still at power 5.
#// (Both plays are on-aspect for a Cunning base + Cunning/Villainy leader: Sun Fac 2 + Change of Heart 6.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:2
WithP1Hand: HMW_243
WithP1Hand: SOR_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Grit
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:1:POWER:5

---

# TwinSuns_AFarSeatUnitIsOfferedAndCanBeGritted
#// "A unit" is a WHOLE-TABLE pool, so at four seats it must reach seats 3 and 4 — not just the single
#// opponent a two-player board makes visible. This cannot pass at two seats: it asserts the offer
#// contains p3/p4 mzIDs and then grits seat 3's unit.
#// The pool comes free from the shared SWUAllUnits collector (ZoneSearch fans "their<Zone>" out across
#// every live opponent), so this section is the guard on that staying true rather than a fix for a
#// known break — a hand-rolled arena walk here would drop the far seats and look correct.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_095:1:2
WithP4GroundArena: SOR_046:1:0
WithP1Hand: HMW_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:CARDID:SOR_095
P3GROUNDARENAUNIT:0:HASKEYWORD:Grit
P3GROUNDARENAUNIT:0:POWER:5
P4GROUNDARENAUNIT:0:NOTKEYWORD:Grit
