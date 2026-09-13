# MoveAFriendlySpaceUnitDown_ThenAttackWithIt
#// HMW_050 Low Altitude Combat. Event, cost 2, [Command][Cunning], Tactic.
#// Text: "Move a space unit to the ground arena (it's now a ground unit). If you do, you may attack
#//        with a ground unit. It gets +2/+0 for this attack."
#//
#// COVERAGE: offer=Offer_SpacePool_SpansBothSidesAndExcludesGroundUnits (clause 1) +
#//                 Offer_AttackPool_FriendlyReadyGroundOnly_IncludingTheUnitJustMoved (clause 2)
#//           decline=DeclineTheAttack_TheMoveStillStands_AndTheTurnStillPasses — clause 2 is the only
#//                 "you may" on the card; clause 1 is mandatory and clause 3 is not a choice at all
#//           boundary=TheBonusIsPowerOnly_TheAttackerStillDiesToFour (+2/+0 pinned against +2/+2 AND
#//                 against no bonus in one board) + TheBonusIsForTHISAttackOnly_NotThePhase
#//           control=N/A (structural — nothing here is owner-scoped: the move sends a unit to its
#//                 OWNER's ground arena keeping its controller, which is what
#//                 MoveAnEnemySpaceUnitDown_ItLandsInTheirGroundArena asserts, and the attack pool is
#//                 self-controlled by the rules of attacking)
#//           reqboundary=RequestBoundary_TheMovedUnitSurvivesIntoTheAttackChoice
#//           modes=2P,TwinSuns ("a space unit" is UNQUALIFIED, so the pool is the whole table and at
#//                 3+ seats it has to reach every live opponent's space arena —
#//                 TwinSuns_AFarSeatsSpaceUnitIsAlsoAValidTarget)
#//                 TeamSuns=N/A (no friendly/enemy word anywhere: clause 1 says "a space unit" — the
#//                 whole table, teammates included, which the unqualified pool already gives — and
#//                 clause 2's scope comes from the rules of attacking, not from a printed qualifier)
#//
#// ⚠ THE UNQUALIFIED "A SPACE UNIT" IS THE JUDGEMENT CALL ON THIS CARD. No "friendly", no "you
#// control" — so EITHER side's space unit is a legal move, and dragging an enemy blocker out of the
#// space arena is a real (if narrow) play. That is the documented recurring shape, and auto-resolve
#// hides every violation of it: with one friendly space unit on the board a friendly-only pool and a
#// both-sides pool behave identically. Only the offer section can tell them apart.
#//
#// ⚠ AND THE MOVED UNIT IS ITSELF A LEGAL ATTACKER. Moving does not exhaust, and by the time clause 2
#// picks "a ground unit" the traveller IS one — so a ready space unit can come down and swing in the
#// same action. That is the card's whole point and it is this section.
#// SOR_237 Alliance X-Wing is 2/3, so the base takes 2 + 2 = 4.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# MoveDown_ThenAttackWithADifferentGroundUnit
#// "You may attack with A GROUND UNIT" — not "with it". The traveller and the attacker are two
#// independent choices, so a unit that was already on the ground may take the swing while the moved
#// unit just stands there.
#//
#// SOR_095 Battlefield Marine is 3/3, so the base takes 3 + 2 = 5 — a different number from the
#// section above, which is what proves the bonus followed the ATTACKER rather than being stapled to
#// the traveller. The X-Wing ends the action still READY: it moved, it did not attack.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_237
P1GROUNDARENAUNIT:1:READY

---

# Offer_SpacePool_SpansBothSidesAndExcludesGroundUnits
#// OFFER CELL for clause 1, and the ONLY section that can pin the unqualified wording. Answering a
#// target proves the branch, never the pool — and with a single friendly space unit on the board a
#// friendly-only implementation auto-resolves onto it and looks perfectly correct.
#//
#// The board holds one of each class the wording must and must not reach:
#//   mySpaceArena-0     a friendly space unit        → in
#//   theirSpaceArena-0  an ENEMY space unit          → in (no "friendly" is printed)
#//   myGroundArena-0    a friendly GROUND unit       → out (it is not a space unit)
#//   theirGroundArena-0 an enemy GROUND unit         → out
#// A pool that is any narrower or any wider than the two space units is a different card.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_084:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0

---

# MoveAnEnemySpaceUnitDown_ItLandsInTheirGroundArena
#// The enemy half of the unqualified pool, taken rather than merely offered. The TIE Fighter is moved
#// out of the space arena and into P2's OWN ground arena — the move changes the unit's arena, never
#// its controller, so a "move it to MY ground arena" implementation shows up here as a stolen unit.
#//
#// The second thing this pins is the "If you do" chain running on the RIGHT board: P1 has no ground
#// unit of their own, so after the move there is no legal attacker and the card ends with no prompt.
#// The traveller is an enemy unit and is never offered as one.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_225
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION
P2BASEDMG:0

---

# NoSpaceUnitAnywhere_NoMove_AndNoAttackIsOffered
#// THE "IF YOU DO" GATE. Clause 2 is chained to clause 1 actually happening, so with no space unit on
#// either side there is nothing to move and the attack is never offered — even though P1 has a
#// perfectly good ready ground unit standing there and would love the free swing.
#//
#// This is the cell that reds if the two clauses are queued independently (the natural way to write
#// them, since clause 2 reads like its own sentence). The event is still spent either way, so
#// DISCARDCOUNT is the control that proves the play happened at all and the section is not passing
#// because nothing ran.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY

---

# Offer_AttackPool_FriendlyReadyGroundOnly_IncludingTheUnitJustMoved
#// OFFER CELL for clause 2. "Attack with a ground unit" carries no printed qualifier, but the rules of
#// attacking supply three: it must be a unit you CONTROL, it must be READY, and — printed — it must be
#// in the GROUND arena. The board holds a counter-example to each:
#//   myGroundArena-0  friendly, ready               → in
#//   myGroundArena-1  friendly but EXHAUSTED        → out
#//   myGroundArena-2  the unit just moved down      → in (moving does not exhaust)
#//   theirGroundArena-0  an ENEMY ready ground unit → out
#// The traveller sitting at index 2 is the load-bearing entry: the pool has to be rebuilt AFTER the
#// move, not captured before it.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: LOF_084:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# TheBonusIsPowerOnly_TheAttackerStillDiesToFour
#// QUANTITY DISCRIMINATION, and it separates THREE readings in one board. SOR_095 Battlefield Marine
#// (3/3) attacks LOF_084 Knight of Ren (4/4):
#//   +2/+0 (correct) → the Marine deals 5 and kills the Knight; the Knight deals 4 and kills the
#//                     Marine. Both are gone.
#//   +2/+2           → the Marine is 5/5, still kills the Knight, and SURVIVES the 4 back.
#//   no bonus        → the Marine deals 3, the Knight lives at 3 damage, and the Marine dies anyway.
#// So "both arenas end at the counts below" is only true for the printed reading. The X-Wing that came
#// down is the surviving unit in P1's arena, which is what makes the count readable.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_084:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_237

---

# TheBonusIsForTHISAttackOnly_NotThePhase
#// DURATION EDGE. "+2/+0 for THIS attack" is a one-shot, not a phase buff — so the moment the attack
#// is over the Marine reads its printed 3 power again.
#//
#// The base damage and the power reading are asserted together on purpose: 5 to the base proves the
#// bonus was live DURING the attack, and POWER:3 afterwards proves it did not outlive it. Either
#// assertion alone passes for the wrong implementation (a permanent +2 gives 5 and then 5; no bonus
#// at all gives 3 and then 3).

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# TheMovedUnitKeepsItsDamageUpgradesAndExhaustedState
#// PERSISTENCE ACROSS THE ARENA MOVE. The traveller is the SAME unit in a different arena, not a new
#// copy of the card — so its damage, its upgrades and its exhausted state all come with it. An
#// implementation that defeated it and created a fresh one in the ground arena would pass every other
#// positive in this file and quietly launder a damaged, exhausted, upgraded unit into a clean one.
#//
#// SOR_237 is 2/3 with an Experience token (SOR_T01, +1/+1) → 3/4, carrying 1 damage and exhausted.
#// Its exhausted state is load-bearing twice over: it is also why no attack is offered here, which is
#// the negative partner to the attack-pool offer section above.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:0:1
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1NODECISION

---

# RequestBoundary_TheMovedUnitSurvivesIntoTheAttackChoice
#// REQUEST-BOUNDARY CELL. The move happens on one request and the attacker is answered on the next,
#// so anything the handler parked in an in-memory global between them — the moved unit, the pool it
#// belongs to — is empty in the fresh process.
#//
#// Same GIVEN and same answer as MoveDown_ThenAttackWithADifferentGroundUnit; only the boundary line
#// is inserted. A lost hand-off shows up as an unbuffed swing or no swing at all, not as an error.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myhandCardIds:HMW_050}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_237

---

# TheTurnPassesExactlyOnce_WhenTheAttackIsTaken
#// ACTION-CLOSE CELL. ⚠ Written WITHOUT `P1OnlyActions`, which is the whole point: that directive
#// hands P1 the initiative so the opponent auto-passes and the turn comes back either way, making a
#// DOUBLE close indistinguishable from a single one. At two seats `TURNPLAYER:2` catches both
#// directions at once — no close leaves it on 1, a double close swaps back to 1.
#//
#// This card is exactly the shape that gets it wrong: an EVENT whose ability ends in an attack, so
#// ownership of the action's end has to pass from the event's own terminator to the attack.
#//
#// ⚠ MEASURED, AND WORTH RECORDING: the single-close behaviour here is STRUCTURAL, not guarded by
#// anything this card does. `_SWUActionCloseGate()` refuses a duplicate close for every card by
#// construction (the per-card SWU_SUPPRESS_AFTERACTION suppressor was deleted in favour of it), so a
#// mutation that calls SWUAfterAction from this card's own handler as well leaves the whole suite
#// green. What these two sections DO pin is that this card participates in the contract at all — both
#// were red before implementation — and they are the only sections in the file that run without
#// `P1OnlyActions`, so a future regression that stopped passing the turn would land here.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;theirResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_050
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
TURNPLAYER:2
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DeclineTheAttack_TheMoveStillStands_AndTheTurnStillPasses
#// DECLINE BRANCH, and the no-op CONTROL for the section above. "You MAY attack" — so refusing is
#// always legal, and refusing must not undo the move that has already happened: the X-Wing stays on
#// the ground, READY, and the base is untouched.
#//
#// Also written without `P1OnlyActions`: the turn must still pass exactly once when the optional half
#// is declined, which is where an event that leans on its attack to close the action goes wrong in the
#// opposite direction from the section above.

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;theirResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_050
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
TURNPLAYER:2
P2BASEDMG:0
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENAUNIT:0:READY

---

# TwinSuns_AFarSeatsSpaceUnitIsAlsoAValidTarget
#// TWIN SUNS CELL, earned by the UNQUALIFIED "a space unit". With three players each for themselves,
#// "a space unit" is every space unit on the table — so the pool has to reach seat 3, not just the
#// one opponent a two-seat board has.
#//
#// A hand-rolled `my…`/`their…` pair or a `GetOpponent()` lookup finds nothing above seat 2 and the
#// far seat's fighter simply never appears in the menu — an over-narrow pool, which shows up as a
#// missing option and nothing else. ⚠ At 3+ seats the mzIDs are SEAT-TAGGED (`p3SpaceArena-0`), which
#// is the frame the pool is read in.

## GIVEN
CommonSetup3P: gyk/rrk/bbk/{myResources:2;myhandCardIds:HMW_050}
WithActivePlayer: 1
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP3SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&p2SpaceArena-0&p3SpaceArena-0

---

# ReportedBoard_PilotedBomberMovesDown_8D8StillGetsToAttackWithPlusTwo
#// ── REPORTED AGAINST ANOTHER ENGINE (2026-09-14) ────────────────────────────────────────────────────
#// "I played Low Altitude Combat to move my Yellow Aces to the ground, and planned to choose 8D8 to
#//  attack, but after I moved the unit, it skipped my action and passed. … even after moving a ready
#//  unit, it also skipped my decision to choose a unit to attack with the +2 buff."
#// The reporter's board: JTL_016 Admiral Ackbar leader (undeployed), two ASH_253 Yellow Aces Bombers in
#// space — one carrying JTL_203 Han Solo as a PILOT (a non-leader Piloting unit: 2/4 -> 4/7, 1 damage)
#// — and a ready ASH_118 8D8 on the ground. The piloted Bomber is moved down; the attacker choice must
#// STILL be offered (8D8 and the moved Bomber are both ready ground units), and 8D8 swings with +2.
#// 8D8 is 1/4: into SOR_046 (3/7) it deals 1 + 2 = 3 and takes 3 back. The bonus is gone afterwards.
#// No P1OnlyActions: the turn must pass once, AFTER the attack — not instead of it.

## GIVEN
CommonSetup: gyw/rrk/{myResources:2;myLeader:JTL_016;myBase:JTL_023}
WithActivePlayer: 1
WithP1Hand: HMW_050
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArena: ASH_253:1:1
WithP1SpaceArenaPilot: 1:JTL_203
WithP1GroundArena: ASH_118:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_118
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:1:CARDID:ASH_253
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:JTL_203
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:7
P2GROUNDARENAUNIT:0:DAMAGE:3
TURNPLAYER:2

---

# ReportedBoard_PilotedBomber_TheAttackPoolIsOffered
#// The pool itself, left pending — the reported symptom was that this decision never appeared. Both
#// ready friendly ground units are in it, INCLUDING the piloted Bomber that just came down.

## GIVEN
CommonSetup: gyw/rrk/{myResources:2;myLeader:JTL_016;myBase:JTL_023}
WithActivePlayer: 1
WithP1Hand: HMW_050
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArena: ASH_253:1:1
WithP1SpaceArenaPilot: 1:JTL_203
WithP1GroundArena: ASH_118:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1DECISIONTOOLTIP:Choose_a_ground_unit_to_attack_with
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
TURNPLAYER:1

---

# LeaderPilotedUnitMovesDown_StaysALeaderUnit_8D8StillAttacks
#// The suspicion raised alongside the report: "the issue was because there was a pilot LEADER on it".
#// JTL_017 Han Solo is deployed for real as a Pilot onto the Bomber (2/4 -> 5/?, "Attached unit is a
#// leader unit"), then Low Altitude Combat moves it down. Moving a leader unit is still a move — the
#// leader stays deployed on it, it stays a leader unit, and the attack is still offered. 8D8 swings.

## GIVEN
CommonSetup: gyw/rrk/{myResources:8;myLeader:JTL_017;myBase:JTL_023}
P1OnlyActions: true
WithP1Hand: HMW_050
WithP1SpaceArena: ASH_253:1:0
WithP1GroundArena: ASH_118:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_118
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:ASH_253
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# LeaderPilotedUnitMovesDown_AndItselfAttacks_ItsOnAttackStillFires
#// The other attacker choice on the same board: the leader-piloted Bomber that just came down swings
#// ITSELF. 5 power + 2 = 7 kills SOR_046 (3/7) exactly (at 5 it would survive on 2); SOR_046 hits back
#// for 3. The Bomber is upgraded (Han is its pilot), so its On Attack "If this unit is upgraded, deal 2
#// damage to a base" fires — pinned to the enemy base. After the attack it reads 5 again and Han is
#// still deployed on it.

## GIVEN
CommonSetup: gyw/rrk/{myResources:8;myLeader:JTL_017;myBase:JTL_023}
P1OnlyActions: true
WithP1Hand: HMW_050
WithP1SpaceArena: ASH_253:1:0
WithP1GroundArena: ASH_118:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:ASH_253
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:0:CARDID:ASH_118
P1GROUNDARENAUNIT:0:READY
P1NODECISION
