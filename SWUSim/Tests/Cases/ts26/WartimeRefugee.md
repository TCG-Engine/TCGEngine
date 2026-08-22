# OnAttackOpponentHealsBase
#// TS26_43 Wartime Refugee (Unit 2/3, cost 1) — On Attack: an opponent heals 1 damage from their base.
#// Wartime Refugee attacks the enemy LAW_124 (7 HP, no base combat damage), so the ONLY base change is
#// the On-Attack heal: P2's base damage 3 → 2. Combat deals 2 to LAW_124.
## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:3}
WithP1GroundArena: TS26_43:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# TwinSuns_TheUNDAMAGEDBaseStaysInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-23 (Pass 1, PROMPT). Asserts the MENU, which is the only thing
#// that pins an eligibility decision: the harness validates candidate lists for MZCHOOSE but NOT for
#// OPTIONCHOOSE, so an outcome-only section passes even when a seat was wrongly filtered out.
#//
#// THE RULE: "An opponent heals 1 damage from their base" is a DRAWBACK the controller pays for a 2/3
#// body at cost 1, so the controller wants it to accomplish as little as possible. Aiming the heal at an
#// UNDAMAGED base makes it heal 0 — a legal and usually BEST line. $eligible must stay null; filtering to
#// "opponents whose base is damaged" would force the drawback to land and nerf the card.
#// ⚠ Contrast LAW_216 in the same sweep, where the filter IS correct. The distinction is whether the
#//   chosen player is being asked to DO something (filter — they must be able to act) or having something
#//   done TO them (don't filter — "can't be affected" may be exactly what the caster wants).
#//
#// Seats 2 and 3 have damaged bases; SEAT 4's base is UNDAMAGED. Seat 4 must still be offered.
#// ⚠ FIXTURE: seats 1/2 base damage comes from CommonSetup ({theirBaseDamage:N}); seats 3/4 use
#//   `WithP{n}Base: CARDID[:damage]`. There is no WithP{n}BaseDamage key.
#// Mutation check: filter $eligible to damaged bases and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: TS26_43:1:0
WithP2GroundArena: LAW_124:1:0
WithP3Base: SOR_021:2
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TwinSuns_TheHealLandsOnTheCHOSENSeat
#// ⚠ THE OUTCOME half. "An opponent" was OtherPlayer() — one seat, chosen by nobody — so the controller
#// could never aim the drawback. P1 attacks seat 2's unit but aims the heal at SEAT 4 (undamaged, so it
#// heals nothing). Seat 2's damaged base must be LEFT ALONE — under the old code it was healed to 2.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: TS26_43:1:0
WithP2GroundArena: LAW_124:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P2BASEDMG:3
P4BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:2
