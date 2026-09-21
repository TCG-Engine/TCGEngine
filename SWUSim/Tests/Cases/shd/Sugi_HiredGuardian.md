# NoSentinelWithoutEnemyUpgrade
#// SHD_052 Sugi — no enemy upgrade (own-side upgrades don't count) → no Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SentinelWhileEnemyUpgraded
#// SHD_052 Sugi — "While an enemy unit is upgraded, this unit gains Sentinel." Guard test for the
#// existing HasConditionalKeyword_Sentinel case (implemented, previously untested). Enemy marine
#// wears an upgrade → Sugi has Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_FarSeatAlone_UpgradedEnemyOnFarSeat
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While an enemy unit is upgraded"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP3GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SHD_052
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_NeitherOpponent_UpgradedEnemyOnFarSeat
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
