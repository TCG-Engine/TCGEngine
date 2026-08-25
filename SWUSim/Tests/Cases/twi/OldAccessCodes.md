# NotMoreUnits_NoDraw
#// TWI_168 Old Access Codes — when the opponent does NOT control more units (both control 1), no draw.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# OppMoreUnits_Draw
#// TWI_168 Old Access Codes (Upgrade, cost 1, Aggression, Item) — "When Played: If an opponent controls
#// more units than you, draw a card." P2 controls 2 units, P1 controls 1 (its host), so P1 draws.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# TwinSuns_AnyOpponentSatisfiesIt
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1). TWI_168 was filed under PROMPT (47) by the
#// text-based inventory, and that was WRONG: "IF an opponent controls more units than you" is an
#// EXISTENTIAL CONDITION, not a target. Nothing downstream needs to know which opponent — you just draw.
#// Giving this card a picker would raise a prompt Premier must never see (its own I1 violation), which is
#// why it is now filed DETERMINED. It is 1 of 7 cards re-filed out of PROMPT for exactly this reason.
#// The bug: OtherPlayer() interrogated ONE seat, so a seat-1 caster never saw seats 3 or 4.
#//
#// P1 controls 1 unit (the upgrade's host). Seat 2 controls 1 — equal, so the OLD code drew nothing.
#// SEAT 3 controls 2, which IS more, so the condition is true and P1 must draw.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent OtherPlayer() is the only comparison there is.
#// Mutation check: revert to OtherPlayer() and this reds while both 2-player sections stay green.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_168}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3SpaceArena: SOR_225:1:0
WithP4GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# TwinSuns_ASumWouldDrawButNoSingleOpponentQualifies
#// ⚠ THE ANTI-SUM CELL, and the one that kills the plausible WRONG fix. "an opponent controls more units
#// than you" is an OR across opponents, NEVER a total across the table.
#// P1 controls 2 units (host + a fighter). Seats 2, 3 and 4 control 1 each — a table TOTAL of 3, which a
#// careless "sum the opponents" rewrite would read as 3 > 2 and DRAW. No SINGLE opponent has more than 2,
#// so the correct answer is NO draw.
#// ⚠ This section also passes under the ORIGINAL bug (seat 2's single unit is not more than 2) — that is
#//   intended. Its job is to constrain the FIX, not to detect the original defect; the section above does
#//   that. Together they pin the predicate from both sides.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_168}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P1DECKCOUNT:2
