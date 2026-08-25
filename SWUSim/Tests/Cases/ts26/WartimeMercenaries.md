# WhenDefeatedOppGivesExp
#// TS26_54 Wartime Mercenaries (Unit 5/5, cost 4) — When Defeated: an opponent may give an Experience
#// token to a unit. The Mercenaries (pre-damaged) attack LAW_124 and die; P2 (the opponent) gives 1
#// Experience to its own LAW_124 (4 power → 5).
## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_54:1:3
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:5

---

# TheOpponentMayDeclineTheExperience
#// TS26_54 Wartime Mercenaries — "an opponent MAY give an Experience token to a unit". P2 declines, so
#// LAW_124 stays at its printed 4 power. The Mercenaries still died to its counter-damage.

## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_54:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENACOUNT:0

---

# TwinSuns_TheCHOSENOpponentGivesTheExperience
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "An opponent MAY give an Experience token
#// to a unit" — the controller chooses WHO gets the option; OtherPlayer() picked one silently.
#// ⚠ NO per-opponent $eligible filter: the thing the chosen player does is "give a token to A UNIT", and
#// that pool is BOARD-WIDE and identical for every opponent. So "opponents who can do something" — the
#// tempting answer — would filter nobody whenever any unit exists and everybody when none does. Gate ONCE
#// globally on the board-wide pool instead (taxonomy shape 3; same as TS26_66).
#// P1's Mercenaries are defeated; P1 hands the option to SEAT 3, who must own the decision. Seat 2 — whom
#// the old code always asked — must have none.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds (the option lands on seat 2).

## GIVEN
CommonSetup: ggk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: TS26_54:1:3
WithP2GroundArena: LAW_124:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
