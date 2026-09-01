# BuffsUnit
#// SOR_124 Tactical Advantage — "Give a unit +2/+2 for this phase." (Event, cost 1, Command)
#// Single unit in play (Blizzard Assault AT-AT SOR_088, 9/9) → auto-target.
#// Power 9+2=11, HP 9+2=11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:11
P1GROUNDARENAUNIT:0:HP:11

---

# CanTargetEnemyUnit
#// SOR_124 Tactical Advantage — "a unit" means ANY unit, enemy included
#// (unlike Attack Pattern Delta, which is friendly-only).
#// Only an enemy unit in play (SOR_088, 9/9) → auto-target it: power 9+2=11, HP 9+2=11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:11
P2GROUNDARENAUNIT:0:HP:11

---

# SimulateRequestBoundary_PhaseBuffSurvivesTheChoose
#// SOR_124 Tactical Advantage — BuffsUnit has a single unit in play, so the target auto-resolves and no
#// request ever ends. A second friendly unit keeps the choose interactive, and the boundary goes before
#// the answer: in production the choose ends the request and the answer arrives in a fresh process.
#// The AT-AT is chosen → +2/+2 for this phase → 11/11, and the untargeted Marine stays 3/3.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_095:1:0    # 2nd legal target, keeps the choose interactive

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:11
P1GROUNDARENAUNIT:0:HP:11
P1GROUNDARENAUNIT:1:POWER:3

---

# BuffLastsForTHISPhaseOnly_PowerBackToPrintedAfterTheRegroup
#// SOR_124 Tactical Advantage — "Give a unit +2/+2 FOR THIS PHASE." The duration is the load-bearing
#// half of the printed text and no existing section outlives the action phase, so a permanent +2/+2
#// passes all three of them.
#// COVERAGE: offer=CanTargetEnemyUnit + SimulateRequestBoundary_PhaseBuffSurvivesTheChoose (the pool is
#//           "a unit", unqualified, so it spans both sides; with one legal unit it auto-resolves and
#//           with two it stays interactive) · decline=N/A (no "you may" — the give is mandatory, and a
#//           board with no unit at all fizzles silently rather than prompting: NoUnitsInPlay_EventIsStill
#//           PlayedAndPaidFor) · control=N/A (a phase-duration stat change stamped on the chosen unit's
#//           own TurnEffects, not on a seat, so it is carried by the object; the enemy-targeting half
#//           is asserted in CanTargetEnemyUnit) · boundary=this section (the buff is present during the
#//           phase and gone after it) · reqboundary=
#//           SimulateRequestBoundary_PhaseBuffSurvivesTheChoose
#//
#// The section is written so that BOTH readings are observable on one board rather than only the
#// after-state (which a never-applied buff would also satisfy):
#//   • DURING the phase the buffed 9/9 AT-AT attacks the enemy base and deals 11 — that number is the
#//     proof the +2/+2 was live.
#//   • Then both players pass, the action phase ends and the regroup phase expires phase-duration
#//     effects. The AT-AT must read its printed 9/9 again.
#// Both decks are seeded so the regroup does not add empty-deck damage to the numbers being asserted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:11
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:9

---

# NoUnitsInPlay_EventIsStillPlayedAndPaidFor
#// SOR_124 Tactical Advantage — the NO-VALID-TARGET cell. With no unit anywhere on the table there is
#// nothing to give +2/+2 to: the ability must return without queueing a decision, and the event is
#// still played — it goes to the discard and its resource is still spent. Per the standing ruling an
#// action that fizzles still pays its cost, so there is deliberately no "use it anyway?" confirmation
#// to answer here.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
