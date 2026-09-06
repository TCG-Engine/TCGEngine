# EventPlay_AnOwnPlayReactionWaitsForTheOpponentsChoice
#// CROSS-QUEUE ORDERING FOR A PLAY. `Block` orders entries only WITHIN one player's queue, so an
#// ability that hands a decision to ANOTHER seat leaves nothing on the caster's queue to wait behind —
#// and the caster's tail (block-5 play reactions, block-10 FINISH_PLAY_CARD, and the bare entry-trigger
#// resume for a unit play) used to run while that seat was still deciding.
#//
#// COVERAGE: offer=N/A (engine ordering, no target pool) · decline=N/A (nothing optional here)
#//           boundary=N/A (no threshold) · control=N/A (no owner-scoped zone)
#//           reqboundary=N/A (the wait is expressed as queue entries, which ARE serialized state)
#//           modes=2P,TwinSuns=TwinSuns_WaitsForAFARSeatsChoice (the wait scans every live seat)
#//
#// REPORTED LIVE 2026-09-05: P1 controls L3-37 (HMW_215, "when you play an event that costs 3 or less:
#// you may play it again") and plays Power of the Dark Side (SOR_041, "an opponent chooses a unit they
#// control. Defeat that unit."). L3-37's ability is a TRIGGERED ability, so per CR 7.6 it resolves only
#// after the event's own ability has finished — which it has not while the opponent is still choosing.
#// The player was asked "play it again?" immediately, before anything had been defeated.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SOR_041
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2HASDECISION
P1NODECISION

---

# EventPlay_TheTailResolvesInOrderOnceTheOpponentHasAnswered
#// The wait must DELAY the tail, not drop it. Once P2 has chosen, P1's reaction offer appears, the
#// replay resolves, and P2 is asked again by the replayed copy — both of their units end up defeated.
#// (P1>Drain surfaces the offer: the reaction is queued while P2's queue is draining, so P1's own queue
#// has to be drained to reach it.)
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SOR_041
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_215

---

# EventPlay_TheActionDoesNotCloseWhileTheOpponentIsDeciding
#// FINISH_PLAY_CARD sits at block 10 on the CASTER's queue, so it used to close the action — swapping
#// the turn — while the opponent still owed the event's own choice. Turns alternate here (no
#// P1OnlyActions) so TURNPLAYER is observable: it must still be P1, and P2's units must still be alive.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_041
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
TURNPLAYER:1
P2HASDECISION
P2GROUNDARENACOUNT:2

---

# UnitPlay_TheActionDoesNotCloseWhileTheOpponentIsDeciding
#// The same defect on the UNIT path, where the tail is the bare entry-trigger resume rather than
#// FINISH_PLAY_CARD — so an events-only fix would have been half a fix. SOR_040 Avenger's When Played
#// hands the choice to the opponent; the turn must not pass until they have made it.
#// (Avenger is [Vigilance][Villainy] cost 9; under a Vigilance base + Vigilance/Heroism leader the
#// Villainy pip is unmatched, so it bills 11 — hence 12 resources.)
## GIVEN
CommonSetup: bbw/rrk/{myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
TURNPLAYER:1
P2HASDECISION
P2GROUNDARENACOUNT:2

---

# UnitPlay_TurnPassesNormallyOnceTheOpponentHasAnswered
#// …and the wait ends. After P2 chooses, the defeat resolves and the action closes exactly once — the
#// turn passes to P2 and no free extra action is taken.
## GIVEN
CommonSetup: bbw/rrk/{myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
TURNPLAYER:2
P2GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1

---

# NoCrossPlayerDecision_TurnPassesImmediatelyAsBefore
#// The control that keeps the change honest: an ordinary event with no cross-player decision must be
#// completely unaffected — the action closes and the turn passes in the same step, exactly as before.
#// Without this, a wait that fired unconditionally would look identical to a wait that fires correctly.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
TURNPLAYER:2
P1NODECISION
P2NODECISION
