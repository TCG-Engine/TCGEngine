# TurnStaysWithTheActorWhileAnotherSeatOwesADecision
#// COVERAGE: offer=N/A (turn-order mechanic, no target pool) · decline=N/A · boundary=N/A ·
#//           control=TurnAdvancesOnceTheDecisionIsAnswered ·
#//           reqboundary=SimulateRequestBoundary_DeferredSwapSurvives ·
#//           modes=2P,TwinSuns (the whole point is a THIRD seat owing work; unreachable at two seats)
#//
#// ⚠ REPORTED 2026-08-26: "P1 is able to take their action while P3 is still resolving the trigger from
#// TIE Bomber of P4's On Attack."
#//
#// The rules gate turned out to be CORRECT — play, attack and pass are all refused while any seat has a
#// pending decision (verified with a control where the identical action succeeds once the decision is
#// answered). What was actually wrong is the TURN PLAYER: SWUSwapTurnPlayer ran unconditionally at the
#// end of the action, so P1 was made the turn player while P3 still owed its indirect assignment. From
#// P1's seat that is indistinguishable from "I can take my action" — their UI says it is their turn and
#// every click is then refused.
#//
#// P4's TIE Bomber (power 0, so no combat damage muddies the read) attacks P3's base. Its On Attack
#// deals 3 indirect to the defending player, and P3 controls a unit, so P3 must ASSIGN it.
#// The turn must still be P4's: the action has not finished resolving.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 4
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP4SpaceArena: JTL_237:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P4>AttackSpaceArena:0:P3B

## EXPECT
SEATCOUNT:4
P3HASDECISION
TURNPLAYER:4

---

# TurnAdvancesOnceTheDecisionIsAnswered
#// THE CONTROL — the deferral must not STRAND the turn. The moment P3 assigns, the swap it was holding
#// back fires and the turn moves on to P1 as normal. Without this section the fix above could "pass" for
#// a build that simply never advances the turn again.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 4
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP4SpaceArena: JTL_237:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P4>AttackSpaceArena:0:P3B
- P3>AnswerDecision:myBase-0:3

## EXPECT
SEATCOUNT:4
P3NODECISION
P3BASEDMG:3
TURNPLAYER:1

---

# NoForeignDecision_TurnAdvancesImmediately
#// The ordinary case must be untouched: with NOTHING owed by another seat, the turn advances at the end
#// of the action exactly as it always did. P3 fields no unit, so the 3 indirect auto-resolves onto its
#// base with no decision at all — and the turn moves straight to P1.
#// This is the section that would catch a fix which deferred the swap unconditionally.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 4
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP4SpaceArena: JTL_237:1:0

## WHEN
- P4>AttackSpaceArena:0:P3B

## EXPECT
SEATCOUNT:4
P3NODECISION
P3BASEDMG:3
TURNPLAYER:1

---

# TwoPlayerControl_TurnAdvancesWithAPendingOpponentDecision
#// ⚠ PREMIER MUST NOT CHANGE. At two seats the same shape exists — P1 attacks with the TIE Bomber and
#// P2 owes the indirect assignment — and Premier has always advanced the turn there. The deferral is
#// keyed on OTHER LIVE SEATS, so it applies here too; this section pins whatever the two-player
#// behaviour is so the fix cannot silently alter the format everyone actually plays.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2HASDECISION
TURNPLAYER:1
