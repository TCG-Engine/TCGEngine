# OpponentForce_Ambush
#// LOF_118 Terentatek (5/5) — "While an opponent controls a Force unit, this unit gains Ambush." With the
#// enemy Plo Koon (a Force unit) in play, it has Ambush; otherwise it does not.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_118:1:0
WithP2GroundArena: LOF_050:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NoOpponentForce_NoAmbush
#// LOF_118 Terentatek — negative: while the opponent controls NO Force unit, this unit does NOT gain
#// Ambush. P2's SOR_046 (Consular Security Force) is a non-Force unit, so Terentatek lacks Ambush.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_118:1:0
WithP2GroundArena: SOR_046:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# TwinSuns_FarSeatAlone_TerentatekSeesAnyOpponentsForceUnit
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While an opponent controls a Force unit"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// This section puts the enabling condition on P3 ONLY, with P2 deliberately clean: a one-seat read
#// answers "no" and a correct read answers "yes". A 2-seat fixture cannot tell them apart.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LOF_118:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: LOF_093:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LOF_118
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# TwinSuns_NoOpponentHasAForceUnit_NoAmbush
#// The negative that keeps the section above honest — neither opponent controls a Force unit.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LOF_118:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
