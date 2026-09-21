# EnemyBounty_GainsShielded
#// SHD_186 Hunter of the Haxion Brood (3-cost, Cunning/Villainy) — "While an enemy unit has a Bounty, this
#// unit gains Shielded." Guard: with the enemy Bounty unit SHD_095 in play it has Shielded; the negative case
#// is covered by the sibling test.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_186
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded

---

# NoEnemyBounty_NoShielded
#// SHD_186 Hunter of the Haxion Brood — negative guard: with no enemy Bounty unit, it does NOT have Shielded.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_186
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded

---

# TwinSuns_FarSeatAlone_BountyEnemyOnFarSeat
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While an enemy unit has a Bounty"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SHD_167:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SHD_186
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded

---

# TwinSuns_NeitherOpponent_BountyEnemyOnFarSeat
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded
