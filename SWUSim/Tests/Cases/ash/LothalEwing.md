# RestoreWhileEnemyUpgraded
#// ASH_057 Lothal E-Wing (Space, 2/3) — While an enemy unit is upgraded, this unit gains Restore 2. With
#// the enemy SEC_080 carrying SOR_120, Lothal E-Wing has Restore.
## GIVEN
CommonSetup: bbw/ggk
WithP1SpaceArena: ASH_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_057
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# NoEnemyUpgrade_NoRestore
#// ASH_057 Lothal E-Wing — it only gains Restore 2 WHILE an enemy unit is upgraded. With no enemy upgrade,
#// attacking the base heals nothing (base stays at 3).
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:3}
WithP1SpaceArena: ASH_057:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:3
P2BASEDMG:2

---

# EnemyUpgraded_RestoreHealsOnAttack
#// ASH_057 Lothal E-Wing — while an enemy unit is upgraded it has Restore 2, so attacking heals 2 from
#// P1's base. Enemy SEC_080 carries SOR_120; P1's base starts at 6 damage and heals to 4 when Lothal
#// E-Wing attacks the enemy base (which takes 2 from Lothal's power).
## GIVEN
CommonSetup: bbw/ggk/{myBaseDamage:6}
WithP1SpaceArena: ASH_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:4
P2BASEDMG:2

---

# FriendlyUpgradeOnly_NoRestore
#// ASH_057 Lothal E-Wing — only an ENEMY upgrade grants Restore 2. With just a friendly upgraded unit
#// (SOR_237 carrying SOR_120) and no enemy upgrade, Lothal E-Wing has no Restore; attacking the enemy
#// base heals nothing (P1's base stays at 6).
## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:6}
WithP1SpaceArena: ASH_057:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 1:SOR_120
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:6
P2BASEDMG:2

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
WithP1SpaceArena: ASH_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP3GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1SPACEARENAUNIT:0:CARDID:ASH_057
P1SPACEARENAUNIT:0:KEYWORDVALUE:Restore:2

---

# TwinSuns_NeitherOpponent_UpgradedEnemyOnFarSeat
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: ASH_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1SPACEARENAUNIT:0:NOTKEYWORD:Restore
