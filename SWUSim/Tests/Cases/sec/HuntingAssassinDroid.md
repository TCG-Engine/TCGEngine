# NoRaidWhenNoEnemyDamaged
#// SEC_134 Hunting Assassin Droid — when NO enemy unit is damaged, the conditional Raid 2 is off, so
#//   it attacks the base for its base 3 power.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# RaidWhileEnemyDamaged
#// SEC_134 Hunting Assassin Droid (Ground, 3/4) — "While an enemy unit is damaged, this unit gains
#//   Raid 2." The enemy SOR_046 is damaged → SEC_134 gets +2 while attacking → base takes 3+2 = 5.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# RaidTurnsOnFromDamageDealtDuringTheSameAttack
#// SEC_134 Hunting Assassin Droid — "WHILE an enemy unit is damaged" is evaluated live, so damage dealt
#// earlier in the same action switches the Raid on. P1 first plays SHD_178 Daring Raid to put 2 on the
#// enemy SOR_046, then attacks the base: the droid now has Raid 2 and hits for 3 + 2 = 5.
#// Companion to NoRaidWhenNoEnemyDamaged, where the identical board with an UNdamaged enemy gives 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SHD_178

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# TwinSuns_FarSeatAlone_DamagedEnemyOnFarSeat
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While an enemy unit is damaged"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:2

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_134
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2

---

# TwinSuns_NeitherOpponent_DamagedEnemyOnFarSeat
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
