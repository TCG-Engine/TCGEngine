# OnAttackDealsToEachPlayersUnit
#// TS26_29 Ziton Moj (Unit 4/4, cost 4) — Ambush. On Attack: for each player, deal 1 damage to a unit
#// that player controls. Ziton attacks LAW_124; the caster deals 1 to its own SEC_080 and 1 to the enemy
#// LAW_124 (which then takes 4 more from combat = 5). Ziton dies to LAW_124's counter.
#// ⚠ ANSWER FORMAT UPDATED 2026-08-24 — the EXPECTATIONS ARE UNCHANGED. The ability was rebuilt from two
#//   sequential MZCHOOSEs (a friendly pick, then an enemy pick) into ONE MZMULTICHOOSE with one pick per
#//   player, because the official ruling says all of Ziton's damage is dealt SIMULTANEOUSLY and the old
#//   shape also only ever reached two seats. So the two picks are now answered together, '&'-joined.
## GIVEN
CommonSetup: ryk/rrk
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1&theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WithNoEnemyUnitsOnlyTheFriendlySideTakesDamage
#// TS26_29 Ziton Moj — "FOR EACH PLAYER, deal 1 damage to a unit that player controls" resolves per
#// player. P2 controls no units, so only P1's SEC_080 takes its 1; Ziton's attack on P2's base still
#// lands for 4.

## GIVEN
CommonSetup: ryk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:4

---

# TwinSuns_OnePickPerSeat_IncludingTheCaster
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "For EACH PLAYER, deal 1 damage to a unit that player
#// controls." The old shape was a two-step sequential pick (one friendly, one enemy) and so reached
#// exactly TWO seats: seats 3 and 4 were never damaged at all.
#// USER RULINGS: "each player" INCLUDES Ziton's own controller (the attacker must damage one of their
#// own units), and the pick is MANDATORY for every player who controls a unit.
#// Every seat here controls a unit, so all four are picked and all four take 1 — including P1's own
#// SEC_080. LAW_124 on seat 2 is also the attack target, so it takes 1 + 4 combat = 5.
#// ⚠ A 2-player version CANNOT FAIL — the old two-step pick already covered both seats. The seat count
#//   IS the test.
#// Mutation check: revert to the sequential my-then-their pick and seats 3/4 take 0.

## GIVEN
CommonSetup: ryk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]
WithP2GroundArena: LAW_124:1:0
WithP3GroundArena: LAW_124:1:0
WithP4GroundArena: LAW_124:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:myGroundArena-1&p2GroundArena-0&p3GroundArena-0&p4GroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P3GROUNDARENAUNIT:0:DAMAGE:1
P4GROUNDARENAUNIT:0:DAMAGE:1

---

# TwinSuns_SeatsWithNoUnitsAreSkipped_AndTheRequiredCountDrops
#// ⚠ USER RULING: a player with no units is simply SKIPPED — no pick, no damage — and the required pick
#// count drops accordingly. The effect is NOT gated on every player having a unit.
#// Seats 1, 2 and 4 control a unit; SEAT 3 CONTROLS NOTHING. So exactly THREE picks are required, and
#// seat 3 contributes nothing to the requirement.
#// ⚠ This is what makes the min/max on the MZMULTICHOOSE dynamic — it is the count of seats WITH units,
#//   not the seat count. Hard-coding it to the number of players would deadlock any board with an empty seat.
#// Mutation check: set the required count to the number of live seats and this reds (the choice can never
#// be satisfied).

## GIVEN
CommonSetup: ryk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]
WithP2GroundArena: LAW_124:1:0
WithP4GroundArena: LAW_124:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:myGroundArena-1&p2GroundArena-0&p4GroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P4GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENACOUNT:0

---

# TwinSuns_ZitonCanKILLHIMSELF_EveryoneElseStillTakesTheirDamage
#// ⚠ THE SELF-KILL CELL — added 2026-08-24 from the user's own scenario, and the sharpest case on the card.
#// USER RULING: Ziton is a LEGAL pick for his own controller's mandatory choice — and when he is the only
#// unit on your board he is the ONLY pick. So the ability can kill him.
#// Consequences, all pinned here:
#//   • Ziton (4/4) is seeded at 3 damage, so his own 1 finishes him.
#//   • Every OTHER player still takes their 1 — the ruling says the damage is SIMULTANEOUS, so his death
#//     does not cancel the rest of the batch.
#//   • His COMBAT damage does NOT resolve. That falls out for free rather than needing a special case:
#//     On Attack resolves BEFORE the damage step, so by then the attacker is gone. Seat 2's LAW_124
#//     therefore shows 1 (the ability) and NOT 1 + 4.
#// ⚠ This is exactly why the implementation resolves each target by UID inside a
#//   SWUSimulDefeatBegin/End window: applying the picks one at a time, by mzID, would let Ziton's own
#//   death re-index the arenas and re-point the later picks (the multi-unit-loop defeat-shift family).
#// Mutation check: drop the UID re-resolution (damage by raw mzID) and the far-seat damage lands wrong.

## GIVEN
CommonSetup: ryk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: TS26_29:1:3
WithP2GroundArena: LAW_124:1:0
WithP3GroundArena: LAW_124:1:0
WithP4GroundArena: LAW_124:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:myGroundArena-0&p2GroundArena-0&p3GroundArena-0&p4GroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENAUNIT:0:DAMAGE:1
P4GROUNDARENAUNIT:0:DAMAGE:1

---

# TwinSuns_ServerRejectsTwoPicksOnTheSameSeat
#// ⚠ THE SERVER-VALIDATION CELL — added 2026-08-24. MZMULTICHOOSE's min/max constrains only the COUNT of
#// picks; it CANNOT express "one per player". So a crafted answer can put two picks on one seat's board
#// and satisfy the count while breaking the rule — the client cap is UX, the server is the rule.
#// ⚠ This section exists because the well-formed sections above CANNOT pin the guard: they all supply a
#//   legal one-per-seat answer, so the guard never fires and deleting it changes nothing. A validation
#//   guard is only pinned by an answer that VIOLATES it.
#//
#// P1 answers with THREE picks — but two of them are on SEAT 2 (indices 0 and 1) and none on seat 3.
#// The server must honour only the FIRST pick per seat:
#//   • seat 2's unit 0 takes 1 (+ 4 combat, as it is also the attack target) = 5
#//   • seat 2's unit 1 takes NOTHING — the duplicate is dropped
#//   • P1's own SEC_080 takes its 1
#//   • seat 3 takes nothing (it was never picked; the answer simply omits it)
#// Without the guard, seat 2's second unit also takes 1.
#// Mutation check: delete the `isset($seen[$seat])` guard and P2GROUNDARENAUNIT:1:DAMAGE:0 reds.

## GIVEN
CommonSetup: ryk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]
WithP2GroundArena: [LAW_124:1:0 SOR_046:1:0]
WithP3GroundArena: LAW_124:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:myGroundArena-1&p2GroundArena-0&p2GroundArena-1

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P3GROUNDARENAUNIT:0:DAMAGE:0
