# WhenDefeatedOppReadyResource
#// TS26_76 Wartime Profiteer (Ground, 3/3) — When Defeated: each opponent may ready a resource.
#// Profiteer (pre-damaged to 1 HP) attacks LAW_124 and dies to the counter; P2 (1 exhausted resource)
#// readies it → P2 ready resources 2 → 3.
## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0
## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES
## EXPECT
P2RESAVAILABLE:3

---

# TheOpponentMayDecline
#// TS26_76 Wartime Profiteer — "each opponent MAY ready a resource". P2 answers no: their exhausted
#// resource stays exhausted, so 2 of their 3 remain ready.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:NO

## EXPECT
P2RESAVAILABLE:2
P2RESCOUNT:3

---

# NoExhaustedResourceMeansNothingToReady
#// TS26_76 Wartime Profiteer — with all of P2's resources already ready there is nothing the ability can
#// do, so it passes silently. The Profiteer still dies to LAW_124's counter-damage.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2RESAVAILABLE:2
P1GROUNDARENACOUNT:0

---

# TwinSuns_EVERYOpponentIsOfferedINDEPENDENTLY
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21. "EACH opponent may ready a resource" was OtherPlayer(): one
#// seat got the offer and the rest of the table got nothing.
#// The Profiteer is P1's, so its opponents are seats 2, 3 and 4 — each gets an INDEPENDENT offer on
#// their own queue. Seat 2 accepts, seat 3 DECLINES, seat 4 accepts.
#// ⚠ Seat 3 declining and keeping its exhausted resource is what makes this discriminating: it proves
#//   three separate offers rather than one shared answer applied to everyone. A version where all three
#//   accept would also pass against a broken implementation that readied everybody outright.
#// ⚠ FIXTURE: resources are seeded as `count:cardID:ready` groups, so `1:SOR_046:0` is the EXHAUSTED one
#//   each seat needs before it can be offered anything at all.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0
WithP3Resources: 2:SOR_046:1,1:SOR_046:0
WithP4Resources: 2:SOR_046:1,1:SOR_046:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:P2G0
- P2>AnswerDecision:YES
- P3>AnswerDecision:NO
- P4>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P2RESAVAILABLE:3
P3RESAVAILABLE:2
P4RESAVAILABLE:3
