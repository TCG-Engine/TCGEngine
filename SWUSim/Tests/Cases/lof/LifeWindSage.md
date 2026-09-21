# EnemyExhausted_Raid
#// LOF_212 Life Wind Sage (3/5) — "While an enemy unit is exhausted, this unit gains Raid 2." With an
#// exhausted enemy in play, attacking the base deals 3 + 2 (Raid) = 5.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_212:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# NoEnemyExhausted_NoRaid
#// LOF_212 Life Wind Sage — negative: with no EXHAUSTED enemy unit it lacks Raid 2. A friendly exhausted
#// unit does not count — only enemy exhaustion matters — so attacking the base deals just 3.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_212:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# TwinSuns_FarSeatAlone_ExhaustedEnemyOnFarSeat
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While an enemy unit is exhausted"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LOF_212:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:0:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LOF_212
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2

---

# TwinSuns_NeitherOpponent_ExhaustedEnemyOnFarSeat
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LOF_212:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
