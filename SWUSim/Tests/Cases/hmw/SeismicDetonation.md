# Ground_ThreeDamageToEachEnemyGroundUnitAtTheNextRegroup
#// HMW_054 Seismic Detonation. Event, cost 6, [Aggression][Cunning], Tactic.
#// Text: "Choose an arena. At the start of the next regroup phase, deal 3 damage to each enemy unit
#//        in that arena."
#//
#// COVERAGE: offer=Offer_BothArenasAreOfferedEvenWithAnEmptyBoard (the arena MENU; there is no target
#//                 pool to assert — the effect names "each" unit rather than choosing one)
#//           decline=N/A (structural — "Choose an arena" is a mandatory PARAMETER, not a modal and not
#//                 a "you may". Picking the arena with nothing in it is a legal, bad play, covered by
#//                 EmptyArena_TheChoiceStillHappens_AndFizzlesCleanly. Contrast LAW_178 Persecutor,
#//                 whose printed "you MAY deal" earns a third "Pass" option this card must not have.)
#//           boundary=DamageIsExactlyThree_ThreeHPDies_SevenHPSurvivesAtThree (the number pinned in
#//                 both directions in one board) + FiresOnce_TheFollowingRegroupIsQuiet (the duration
#//                 edge: "the NEXT regroup phase", not every regroup)
#//           control=ControlChange_AUnitYouNowControlIsNoLongerAnEnemy — "enemy" is read at the moment
#//                 the delayed effect RESOLVES, a whole phase after it was created
#//           reqboundary=RequestBoundary_TheArenaChoiceSurvivesToTheRegroup
#//           modes=2P,TeamSuns ("each ENEMY unit" — in Team Suns a teammate's unit is friendly and
#//                 must be spared, covered by TeamSuns_TeammatesUnitsAreSpared)
#//                 TwinSuns=N/A (no player reference to prompt on; the sweep reads their<Arena>, whose
#//                 fan-out across every live opponent is the same code path the Team Suns section
#//                 already drives — and that section is strictly sharper, since it must both REACH the
#//                 far seats and EXCLUDE one of them)
#//
#// ⚠ THIS CARD DOES NOTHING WHEN IT IS PLAYED. Everything about it is deferred a whole phase, so the
#// arena choice must be written into the GAMESTATE at play time and read back at RegroupPhaseStart —
#// there is no in-memory anything that survives that gap. The nearest built shapes are LAW_245 Salvaged
#// Materials ("at the start of the next regroup phase, defeat it") and HMW_200 Rish Loo (control
#// returns then), both of which park a global on the player and consume it in RegroupPhaseStart.
#//
#// This section is the plain positive: two enemy ground units, both take 3, and the 3/3 dies of it.
#// SOR_046 Consular Security Force is 3/7 (survives, so its DAMAGE reads the exact number) and SOR_095
#// Battlefield Marine is 3/3 (dies, so lethality is proven too).

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# NothingHappensWhenTheEventIsPlayed
#// ⚠ THE DELAY IS THE CARD, and it is the one thing a same-phase implementation would get wrong while
#// passing every "the damage landed" section in this file. Identical board and identical answer as the
#// section above, with the Pass REMOVED: the action phase is still running, so the enemy units must be
#// completely untouched.
#//
#// Also pins that the event leaves no decision hanging around after the arena is chosen — a handler
#// that queued its damage as a second prompt would show up here as a pending decision, not as damage.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION
P1DISCARDCOUNT:1

---

# Space_OnlyTheChosenArenaIsHit
#// THE ARENA PARAMETER IS LOAD-BEARING. Enemies in BOTH arenas, and Space is chosen: the space units
#// take 3 and the ground unit takes nothing. An implementation that ignored the answer and swept both
#// arenas (or hardcoded Ground) passes the opening section and fails here.
#//
#// TWI_111 Republic ARC-170 is 3/4, so it survives and its DAMAGE reads the exact number; SOR_225
#// TIE/ln Fighter is 2/1 and is destroyed.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2SpaceArena: TWI_111:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>Pass

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:TWI_111
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# FriendlyUnitsInTheChosenArenaAreSpared
#// THE "ENEMY" GATE, isolated. A friendly unit satisfies every other condition — right arena, in play,
#// present at the regroup — so it is the only fixture that separates "each enemy unit" from "each
#// unit". Seismic Detonation is a Tactic and several cards in the neighbourhood (LAW_178 Persecutor)
#// deliberately hit BOTH sides, which is exactly why this one has to be pinned.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_BothArenasAreOfferedEvenWithAnEmptyBoard
#// OFFER CELL. Answering an arena proves the branch, never the MENU — so this section leaves the
#// choice pending and reads the option list itself, on a board with no units at all.
#//
#// Two things are pinned: both arenas are always present (a menu filtered to "arenas with enemy units
#// in them" would offer nothing here and the event would silently resolve into a no-op), and there is
#// NO third "Pass" entry — this card's choice is a mandatory parameter, unlike LAW_178 Persecutor's
#// "you MAY deal 3 damage to each unit in that arena", which offers Ground&Space&Pass.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:Ground
P1OPTIONHAS:Space
P1OPTIONNOT:Pass

---

# EmptyArena_TheChoiceStillHappens_AndFizzlesCleanly
#// NO-VALID-TARGET CELL. The enemies are all in the GROUND arena and Space is chosen — a legal, bad
#// play. The regroup must arrive with nothing damaged, no crash and no dangling decision, and the
#// event is still spent (it is in the discard, and the resources are gone).

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# DamageIsExactlyThree_ThreeHPDies_SevenHPSurvivesAtThree
#// QUANTITY DISCRIMINATION, both directions in one board. The 3/7 survives and its DAMAGE reads 3 —
#// which rules out 2 and 4 alike — while the 3/3 is destroyed, which rules out anything below 3 that a
#// bare DAMAGE assertion on a fat unit would not catch. A 2-damage implementation leaves the Marine
#// alive at 2; a 4-damage one shows 4 on the Consular.
#//
#// The zero case is the section above (an arena with no enemy in it), and the "every unit takes its
#// own 3" scope is here too: this is not a shared pool being divided.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# UnitsThatArrivedAfterTheEventAreHitToo
#// THE BOARD IS READ AT THE REGROUP, NOT AT PLAY. Nothing is "marked" when the event resolves — the
#// clause says "each enemy unit in that arena" at the moment it goes off, so a unit that was still in
#// hand when the detonation was set lands in it all the same.
#//
#// ⚠ FIXTURE: SOR_046 is Vigilance/Heroism against P2's Aggression/Villainy board, so it costs 4 + a
#// 4-point aspect penalty = 8. Seeded with fewer resources than that, P2's play is a SILENT NO-OP and
#// the arena simply stays empty — which reads exactly like the bug this section is hunting.
#//
#// An implementation that snapshotted the arena at play time (the natural way to write it if you think
#// of the effect as targeting) passes every other positive in this file and leaves this newcomer
#// undamaged. Here the arena is EMPTY when the event is played, so the snapshot version does nothing
#// at all.

## GIVEN
CommonSetup: ryk/rrk/{myResources:8;theirResources:8;theirhandCardIds:SOR_046}
WithActivePlayer: 1
WithP1Hand: HMW_054
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ControlChange_AUnitYouNowControlIsNoLongerAnEnemy
#// CONTROL CELL. "Each ENEMY unit" is resolved when the delayed effect goes off, a whole phase after
#// the arena was chosen — so a unit that has since come under the caster's control is friendly by then
#// and must be spared, even though it was an enemy when the detonation was set.
#//
#// `WithP1GroundArenaControlled: SOR_046:2` seeds a unit OWNED by seat 2 and CONTROLLED by seat 1, so
#// an implementation that scanned by OWNER rather than by controller (or that stored a list of enemy
#// UIDs at play time) damages it here and passes everywhere else. The genuinely enemy unit alongside
#// it is the control: it proves the sweep still ran.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# FiresOnce_TheFollowingRegroupIsQuiet
#// DURATION EDGE. "At the start of the NEXT regroup phase" is a one-shot, not a standing aura — so
#// after the first regroup consumes it, the second must leave the survivor at 3 damage rather than 6.
#//
#// The marker this card parks is a PERMANENT global (nothing else survives a phase), and a permanent
#// global that is never consumed is an infinite detonation. This is the only section that would catch
#// that, and every other section in the file passes with the consume deleted.
#//
#// ⚠ REACHING A SECOND REGROUP TAKES THE WHOLE ROUND, NOT A SECOND PASS — and getting this wrong makes
#// the section a BROKEN PROBE that cannot fail. Measured (twice) with the consume deleted: it stayed
#// green because the second regroup never happened at all. The round is
#//     Pass  →  phase RES (the regroup's "play a resource" prompt; a second Pass here does nothing)
#//     ResourcePass ×2  →  phase MAIN of the next round
#//     Pass, Pass  →  the second regroup
#// ⚠ and the last step needs BOTH seats. P1OnlyActions hands P1 the initiative, so P2 auto-passes in
#// round ONE and a lone `P1>Pass` is enough there — but not in round two.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237 SEC_080 SOR_095 SOR_231 SOR_242]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237 SEC_080 SOR_095 SOR_231 SOR_242]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# TwoCopies_SameArena_EachResolves
#// VALUE-CLASS / STACKING CELL. Two detonations set in the same phase for the same arena are two
#// independent delayed effects, not one flag that gets overwritten — so the survivor takes 3 twice.
#//
#// A marker stored as a single per-player value ("the arena P1 chose") silently collapses the second
#// copy into the first and shows 3 here. SOR_046 is 3/7 so it survives both hits and can carry the
#// count; the assertion is the whole point of the section.

## GIVEN
CommonSetup: ryk/rrk/{myResources:12;myhandCardIds:HMW_054,HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# TwoCopies_DifferentArenas_EachKeepsItsOwnArena
#// The partner to the section above, and the sharper half: two detonations set for DIFFERENT arenas.
#// Each must remember the arena IT was given, so the ground enemy and the space enemy each take 3 and
#// neither takes 6.
#//
#// This is what a per-copy marker buys over a per-player one. It also rules out the other collapse —
#// "the last arena chosen wins" — which would leave the ground unit clean and hit the space one twice.

## GIVEN
CommonSetup: ryk/rrk/{myResources:12;myhandCardIds:HMW_054,HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: TWI_111:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:TWI_111
P2SPACEARENAUNIT:0:DAMAGE:3

---

# RequestBoundary_TheArenaChoiceSurvivesToTheRegroup
#// REQUEST-BOUNDARY CELL. This card crosses the boundary twice over — the arena is answered on one
#// request and the damage is dealt in a different PHASE — so a handler that parked the arena in an
#// in-memory global would find it empty and either fizzle or default to Ground.
#//
#// The fixture makes the default-to-Ground failure visible rather than silent: Space is chosen with an
#// enemy in BOTH arenas, and the boundary is inserted immediately after the answer. A lost choice
#// damages the ground unit instead of the space one, which the ground unit's DAMAGE:0 catches.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: TWI_111:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>SimulateRequestBoundary
- P1>Pass

## EXPECT
P2SPACEARENAUNIT:0:CARDID:TWI_111
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# TeamSuns_TeammatesUnitsAreSpared
#// TEAM SUNS CELL, earned by the word "ENEMY". In a 2v2 game a teammate's unit is friendly but you do
#// not control it — so the sweep must be a TEAM relation, not "every seat that isn't me". Seats 1 and
#// 3 are Red, 2 and 4 are Blue.
#//
#// Seat 1 detonates the ground arena. Both Blue seats' units take 3; the Red teammate on seat 3 takes
#// nothing. That is two assertions no two-seat board can make: the sweep has to REACH seat 4 (a
#// hand-rolled "the opponent" or a `GetOpponent()` lookup silently finds nobody up there) and it has
#// to STOP at seat 3.
#//
#// ⚠ The actor is seat 1: CommonSetup dresses seats 1-2 only, so a far seat cannot hold a hand to play
#// from. Seats 3/4 carry boards here, which is what they are for. All four decks are seeded because
#// reaching the regroup with an empty deck fires the CR 6.1 deck-out draw, which has faked an engine
#// bug before.

## GIVEN
CommonSetup: ryk/rrk/{myResources:6;myhandCardIds:HMW_054}
WithTeams: true
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP3Deck: [SOR_046 SOR_128 SOR_225 SOR_237]
WithP4Deck: [SOR_046 SOR_128 SOR_225 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3GROUNDARENAUNIT:0:DAMAGE:0
P4GROUNDARENAUNIT:0:CARDID:SOR_046
P4GROUNDARENAUNIT:0:DAMAGE:3
