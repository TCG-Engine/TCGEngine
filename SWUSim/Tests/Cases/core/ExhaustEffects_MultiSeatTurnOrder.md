# "Every exhaust effect skips the next player's turn" — bug report #1023, game 4161 (3 seats).
#
# The report generalises #1021 (JTL_210 The Mandalorian) to the whole family. There are THREE distinct
# ways the engine exhausts something, and a defect in any one of them would present identically to a
# player, so all three are pinned here:
#   1. the shared EXHAUST_UNIT continuation  (IBH_018 Go for the Legs — a pure exhaust event)
#   2. exhaust as an ability's COST          (ASH_011 Cad Bane, "Action [Exhaust]: …")
#   3. an inline $obj->Status = 0            (JTL_210, which never touches EXHAUST_UNIT)
#
# ⚠ WHY THREE SEATS AND NOT TWO. SWUSwapTurnPlayer() advances via NextLiveSeat(), which at two seats is
# an INVOLUTION — a double swap returns to the acting player, so it reads as "I got an extra action",
# and any compensation that swaps back is indistinguishable from correct behaviour. At three seats the
# same double swap ADVANCES TWICE and the middle seat never acts. Game 4161 is a 3-seat table, so
# TURNPLAYER must be pinned to the EXACT next seat; "not me" is not an assertion here.
#
# ⚠ NO `P1OnlyActions`. That directive claims initiative so the opponents auto-pass, which makes a
# double swap indistinguishable from a single one and would render every section below vacuous.

---

# ThreeSeat_ExhaustEvent_SharedContinuation
#// IBH_018 Go for the Legs (cost 1 event): "Exhaust an enemy ground unit." Routes through the shared
#// EXHAUST_UNIT continuation. Exactly ONE enemy ground unit exists, so the target auto-resolves and no
#// decision is left pending to confuse the turn assertion.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: IBH_018
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2

---

# ThreeSeat_ExhaustAsAbilityCost_LeaderAction
#// ASH_011 Cad Bane — "Action [Exhaust]: Deal 1 damage to a unit with 2 or more remaining HP." Here the
#// exhaust is the COST of the ability rather than its effect. One qualifying target (SOR_046, 3/7) so
#// the choose auto-resolves.
## GIVEN
CommonSetup3P: rrk/bbk/bbk/{
  myLeader:ASH_011
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1
TURNPLAYER:2

---

# ThreeSeat_InlineExhaust_TheMandalorian
#// JTL_210 The Mandalorian — "When played as a unit: Exhaust up to 2 ground units", written as a direct
#// $obj->Status = 0 rather than through EXHAUST_UNIT. This is the card from report #1021; three seats is
#// the shape that report describes and the 4-seat file does not cover.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P3GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2

---

# ThreeSeat_Baseline_PlainUnitPlay_NoExhaustAnywhere
#// The control. A vanilla unit with no exhaust effect at all must move the turn exactly one seat. If
#// this ever reds, the defect is in the seat rotation itself and none of the sections above mean what
#// they claim.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SOR_046
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
TURNPLAYER:2

---

# ThreeSeat_MiddleSeatActsAfterAnExhaust
#// The report's actual claim, stated as a board outcome rather than a variable: after P1's exhaust
#// effect, SEAT 2 must actually GET its turn — proven by seat 2 taking an action (a pass) and the turn
#// then landing on seat 3. If seat 2 had been skipped, the turn would already be 3 and P2's pass would
#// move it to 1.
#// ⚠ Three enemy ground units here, so IBH_018's target does NOT auto-resolve — the choose must be
#// answered explicitly. An earlier draft omitted that, left the decision pending, and the follow-up
#// action silently did nothing; the section failed for the fixture, not the engine.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: IBH_018
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-1
- P2>Pass
## EXPECT
P2GROUNDARENAUNIT:1:EXHAUSTED
TURNPLAYER:3
