# OnAttack_CasterDeclines
#// SHD_246 Grey Squadron Y-Wing — the damage is a "may": after the opponent chooses its unit, P1 declines
#// (NO), so no damage is dealt to it (only the 1 base combat damage from the attack itself).

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SHD_246:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:1

---

# OnAttack_OpponentChoosesUnit_Deal2
#// SHD_246 Grey Squadron Y-Wing (2-cost 1/3 space) — "On Attack: An opponent chooses a unit or base they
#// control. You may deal 2 damage to it." Grey Squadron attacks P2's base; on attack, P2 chooses its own
#// space unit SOR_046, and P1 opts to deal 2 to it. (Cross-player, so WithActivePlayer:1 — not P1OnlyActions,
#// which would auto-pass P2 and eat its choice.)

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SHD_246:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:1

---

# TwinSuns_EmptyBoardedOpponentIsSTILLEligible
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-23 (Pass 1, PROMPT). Asserts the MENU; an outcome-only section
#// cannot pin eligibility (the harness does not validate OPTIONCHOOSE candidates).
#//
#// ⚠⚠ THIS IS THE SWEEP'S SHARPEST NEAR-MISS. SHD_014 Cad Bane's clause is ONE WORD different —
#//   Cad Bane: "an opponent chooses a unit they control"
#//   SHD_246:  "an opponent chooses a unit OR BASE they control"
#// Cad Bane therefore NEEDS a has-a-unit filter; SHD_246 must have NO filter at all, because every live
#// opponent always controls a base and so can always choose. Copying Cad Bane's gate onto this card would
#// delete perfectly legal (and often best) picks. Textbook "shared code shape is not a shared bug".
#//
#// SEAT 4 controls NOTHING — no units at all — and must still appear in the picker.
#// Mutation check: pass a has-a-unit $eligible filter and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: rrk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1SpaceArena: SHD_246:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackSpaceArena:0:P3B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1
