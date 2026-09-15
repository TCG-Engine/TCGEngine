# EventDraw_OpponentsReaction_ClosesTheActionOnce
#// CR 7.6.8 draw-trigger bracket (2026-09-11 — see SWUBeginDeferDrawTriggers): an event's draw triggers wait
#// until the event has fully resolved, then resolve inside its action, and the action is closed BEHIND them.
#// The cross-seat case: P1 plays SOR_171 Mission Briefing choosing "You" (P1 draws 2); P2's JTL_111 Seasoned
#// Fleet Admiral ("When an opponent draws 1 or more cards during the action phase: You may give an
#// Experience token to a unit") reacts on P2's OWN queue. The released close must wait behind that
#// reaction on P2's queue — a close left on P1's queue alone would never drain. Without P1OnlyActions:
#// the turn passes to P2 exactly once and no duplicate close is attempted.

## GIVEN
CommonSetup: bbk/bbk/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_171
WithP1Resources: 12
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP2GroundArena: JTL_111:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:2
P2GROUNDARENAUNIT:0:POWER:2
P1NODECISION
P2NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# EventCloseAlreadyHopped_ReleasedTriggerOnTheOtherSeat
#// The shape that breaks a close queued on the CASTER's queue. P2 plays SHD_244 No Bargain: P1 must discard
#// (so the event's close hops onto P1's queue — P1 owes a decision), then P2 draws a card. P2's draw releases
#// P1's JTL_111 Seasoned Fleet Admiral reaction onto P1's queue — the queue that is running. The re-close
#// must go behind it THERE: queued on P2's queue it is a lone CUSTOM on a seat that is not acting, never
#// drains, and the action is closed twice (found by sec/PadmeAmidala_… Deployed_TriggersEachDiscardEvent).

## GIVEN
CommonSetup: bbk/bbk/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 12
WithP2Hand: SHD_244
WithP2Deck: [SOR_128 SOR_128]
WithP1Hand: [SOR_095 SOR_063]
WithP1GroundArena: JTL_111:1:0

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1NODECISION
P2NODECISION
TURNPLAYER:1
NOEXTRAACTION

---

# NestedDraw_RunningOnTheOpponentsQueue_StillResolves
#// The NESTED hold (CR 7.6.11 — a draw inside a TRIGGERED ability resolves right after it). LOF_065 Watto's
#// On Attack: "an opponent chooses one: you give an Experience token to a friendly unit, or you draw a
#// card." P2 picks Draw, so P1's draw happens in a continuation running on P2's QUEUE. The release marker
#// must go on that running queue: left on P1's queue it never drains, the combat resume hops back to wait
#// on it, and the attack never deals damage (found by lof/Watto_… and sec/CikatroVizago_…). Here ASH_169
#// Axe Woves' draw trigger resolves and Watto's attack still lands.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1GroundArena: ASH_169:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P2BASEDMG:1
P1NODECISION
P2NODECISION
