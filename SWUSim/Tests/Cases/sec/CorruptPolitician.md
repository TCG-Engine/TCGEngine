# EqualUnitsNoSentinel
#// SEC_079 — when you do NOT control more units than the opponent (here 1 each), SEC_079 has no Sentinel.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# MoreUnitsSentinel
#// SEC_079 Corrupt Politician (Ground, 2/2) — "While you control more units than an opponent, this unit
#//   gains Sentinel." P1 controls 2 units vs P2's 0 → SEC_079 has Sentinel.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_079:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_FarSeatAlone_MoreUnitsThanTheFarSeatOnly
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While you control more units than an opponent"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.
#// P1 holds 2 units. P2 holds 3 (MORE than P1, so P2 alone never satisfies it) and P3 holds 1 (FEWER,
#// so P3 does). "more units than AN opponent" needs only one to be below you — checking P2 alone says no.
## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [SEC_079:1:0 SEC_080:1:0]
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_079
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_NeitherOpponent_MoreUnitsThanTheFarSeatOnly
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [SEC_079:1:0 SEC_080:1:0]
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]
WithP3GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
