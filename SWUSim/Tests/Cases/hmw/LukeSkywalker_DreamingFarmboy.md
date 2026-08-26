# FirstRound_EntersReady
#// COVERAGE: offer=N/A (no target pool — the card chooses nothing) ·
#//           decline=N/A (no optional branch; the readiness replacement is not a "may") ·
#//           boundary=FirstRound_EntersReady vs SecondRound_EntersExhausted_RealRoundAdvance
#//                    (round 1 vs round 2 — the N/N+1 pair that pins "the FIRST round") ·
#//           control=N/A (no owner-scoped zone and no seat-relative wording; "this unit" and
#//                    "the first round of the game" are both control-independent) ·
#//           reqboundary=SimulateRequestBoundary_ReadinessSurvivesTheBoundary
#//
#// HMW_208 Luke Skywalker - Dreaming Farmboy (Ground, 1/3, cost 1, Cunning+Heroism, Force/Fringe, unique)
#// "Raid 1.
#//  While it's the first round of the game, this unit enters play ready."
#//
#// ⚠ PREVIEW SET — HMW has no entry in card-specific-rulings.md, so the reading below is reasoned from
#// the CR plus the closest released analogues (SEC_170 Corellian Hounds / LAW_210 / LAW_223 / ASH_224,
#// the four existing "conditional enters play ready" cards) and is FLAGGED as such rather than sourced.
#//
#// CR 8.22.f: units enter play EXHAUSTED unless the card says otherwise. This is the positive half —
#// played on round 1, Luke replaces that default and enters READY.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 1
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_208
P1GROUNDARENAUNIT:0:READY

---

# SecondRound_EntersExhausted_RealRoundAdvance
#// ⚠⚠ THE LOAD-BEARING NEGATIVE, driven through the REAL round counter rather than a fixture flag.
#//
#// `SWUUnitEntersReady()` is a bare substring match for "this unit enters play ready" — and HMW_208's
#// printed text CONTAINS that phrase inside its own conditional clause. So without a per-card re-gate the
#// card enters ready in EVERY round, silently ignoring the only thing its text actually says. That is the
#// documented shape of the four existing conditional cards, which is why the engine keeps an explicit
#// per-card branch for each of them.
#//
#// This section advances a genuine round (both players pass to reach regroup, then both resource-pass so
#// the ready step runs and RegroupPhaseStart increments TurnNumber) and only THEN plays Luke. It therefore
#// proves the gate reads the real counter, not the WithRound directive I added for the sibling section.
#// ⚠ Both decks are seeded: an empty deck at regroup costs the base 6 damage (CR 6.1 deck-out), which is
#// noise here and has faked threshold results on other cards.
#// ⚠ The trailing `P2>Pass` is load-bearing: P1OnlyActions hands P2 the CLAIMED initiative, so P2 LEADS
#// round 2 and is the turn player when the new action phase opens — without it P1's play is refused by
#// the turn-player gate and the section fails with an empty arena, which reads exactly like the card
#// failing to resolve. (Measured: TURNPLAYER is 2 at that point.)

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 1
WithP1Hand: HMW_208
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_208
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# SecondRound_EntersExhausted
#// The same negative in the cheap fixture form (WithRound: 2), which also pins the new directive itself.
#// Kept alongside the real-round-advance section deliberately: this one isolates the GATE, that one
#// proves the gate is wired to the counter the game actually increments.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 2
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# LaterRound_EntersExhausted
#// The gate is "the FIRST round", not "an odd round" / "not round 2" — round 5 must also be exhausted.
#// A lone round-2 negative passes for any `!== 2` or parity implementation.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 5
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# FirstRound_ReadyLukeAttacksImmediately_WithRaid
#// BOTH CLAUSES TOGETHER — and the section that shows the readiness is REAL rather than a display flag.
#// Entering ready on round 1 is only worth anything because it lets Luke act the turn he lands. He is
#// 1 power with Raid 1 (+1 while attacking), so an immediate attack on the enemy base deals 2.
#// This also covers the Raid clause, whose keyword wiring is auto-derived ($Raid_Cards['HMW_208'] => 1).

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 1
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# SecondRound_ExhaustedLukeCannotAttack
#// The consequence-negative partner of the section above: on round 2 Luke enters exhausted, so the very
#// same attack does NOTHING. Asserting the enemy base at 0 (rather than only the EXHAUSTED flag) is what
#// proves the flag is load-bearing — a build that set the flag but still allowed the attack would pass
#// every EXHAUSTED assertion in this file and fail only here.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 2
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# TwinSuns_FarSeatAlsoEntersReadyOnRoundOne
#// TWIN SUNS. HMW_208's text contains no player reference at all — "this unit" and "the first round of
#// the game" are both seat-independent — so there is no prompt/loop/determined-seat classification to
#// make. What DOES need proving is that the round is read GLOBALLY: TurnNumber is one shared counter, not
#// a per-seat value, so a far seat must see the same round 1.
#//
#// Seat 3 plays Luke and must get the ready replacement. This section cannot pass at two seats — seat 3
#// does not exist there.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithRound: 1
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
#// Seat 3 has a base but no leader, so BOTH of Luke's pips are off-aspect (+4): cost 1 -> 5.
WithP3Resources: 6
WithP3Hand: HMW_208

## WHEN
- P3>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:HMW_208
P3GROUNDARENAUNIT:0:READY

---

# SimulateRequestBoundary_ReadinessSurvivesTheBoundary
#// REQUEST BOUNDARY. Luke raises no interactive decision, so the boundary goes between the two ACTIONS
#// that write and read the state: the play (which resolves the round gate and stamps Status) and the
#// attack (which needs him ready).
#//
#// The round counter is a real serialized schema zone (TurnNumber), so this is expected to hold — the
#// cell is written anyway because the axis is the one a later validation pass provably never backfills,
#// and because "expected to hold" is exactly what was said about every transient global that turned out
#// not to be. Same board and same numbers as FirstRound_ReadyLukeAttacksImmediately_WithRaid, so any
#// divergence is attributable to the boundary alone.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithRound: 1
WithP1Hand: HMW_208

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
