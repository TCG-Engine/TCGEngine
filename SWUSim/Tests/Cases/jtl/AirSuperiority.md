# MoreSpace_Deal4
#// JTL_125 Air Superiority — If you control more space units than an opponent, deal 4 damage to a ground
#// unit that opponent controls. P1 has a space unit (P2 none), so it deals 4 to SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NotMoreSpace_NoDamage
#// JTL_125 Air Superiority — the "if you control more space units than an opponent" gate FAILS: P1 has
#// zero space units while P2 has one (SOR_237). No damage is dealt; the event resolves with no effect and
#// goes to the discard, and the enemy ground unit SOR_046 is untouched. (Playing it anyway with the gate
#// failed deals no damage.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# TwinSuns_ComparisonAndDamagePoolNameTheSAMESeat
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "If you control more space units than AN OPPONENT, deal 4 to
#// a ground unit THAT OPPONENT controls." One opponent, both clauses. TWO defects, OPPOSITE directions:
#//   (a) TOO NARROW — the comparison asked one seat, so out-flying seat 3 but not seat 2 was "no".
#//   (b) TOO WIDE — 'side'=>'their' fans out, so you could qualify against seat 2 and hit SEAT 4's unit.
#//       The pool GREW, so nothing looked broken and every 2-player section stayed green.
#// ⚠ FILTER to opponents you actually out-fly — against anyone else the condition is false, so they are a
#//   choice among nothing. P1 has 2 space units; seat 2 has 2 (NOT out-flown), seats 3 and 4 have 1 each.
#// Menu must be exactly P3&P4, and the damage pool must be seat 3's ground unit alone.
#// ⚠ A 2-player version CANNOT FAIL — one opponent is both the comparison and the pool.
#// Mutation check: drop $eligible and P2 appears; drop 'ofSeat' and p2/p4 units appear in the pool.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1SpaceArena: [SOR_237:1:0 JTL_069:1:0]
WithP2SpaceArena: [SOR_237:1:0 JTL_069:1:0]
WithP3SpaceArena: SOR_237:1:0
WithP4SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P2
P1OPTIONNOT:P1
