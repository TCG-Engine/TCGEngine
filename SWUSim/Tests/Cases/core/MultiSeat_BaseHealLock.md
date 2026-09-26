#// CORE — "Bases can't be healed" is a GLOBAL lock, so it has to be read across EVERY seat.
#//
#// Two locks share one gate in OnHealBase (CombatLogic.php):
#//   • the phase flag SWU_NOHEAL_BASE — LAW_197 Shifty Suspects (On Attack), SOR_160 Wolffe
#//     (When Played/On Attack), cleared at RegroupPhaseStart;
#//   • the continuous in-play lockers _SWUBasesCantBeHealed() — TWI_132 Confederate Tri-Fighter,
#//     HMW_159 General Grievous (Scourge of Dathomir).
#// Both scans were written as `seat 1 || seat 2`, which is every seat there is at two players and
#// only HALF the table in a 3-/4-seat Twin Suns game. A far-seat locker therefore stopped nothing:
#// seat 3 could field a Tri-Fighter, or attack with Shifty Suspects, and seats 1 and 2 kept healing.
#//
#// Fixture shape used by every section: P1's leader is SOR_002 Iden Versio, whose Action [Exhaust]
#// heals 1 from her own base if an enemy unit was defeated this phase. Her condition is met by
#// seeding the DEFEATED unit's controller with SWU_FRIENDLY_DEFEATED (the flag Iden actually reads —
#// see cards/sor/IdenVersio_InfernoSquadCommander.php), so the sections turn purely on the heal
#// gate and not on a combat that might fail for its own reasons. P1's base starts at 3 damage:
#// LOCKED → stays 3, UNLOCKED → heals to 2. The far-seat locker always attacks a base OTHER than
#// P1's, so nothing else can move the number being asserted.
#//
#// ⚠ Each section needs its own control. A heal that silently never happened reads exactly like a
#// lock that worked, so the "…HealsNormally" sections below are what make the 3s load-bearing.

# FarSeat3_ShiftySuspects_LocksHealingForSeat1
#// RED before the fix. Seat 3 attacks with LAW_197 Shifty Suspects, stamping SWU_NOHEAL_BASE on
#// seat 3. Iden's heal must be blocked — the card says "Bases can't be healed for this phase", not
#// "…for the seats I happen to share a gate with". Pre-fix the seat-3 flag was invisible to
#// OnHealBase and P1's base healed to 2.

## GIVEN
CommonSetup3P: bbk/bbk/rrw/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 3
WithP3GroundArena: LAW_197:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P3>AttackGroundArena:0:P2B
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3

---

# FarSeat3_NoAttack_IdenHealsNormally
#// CONTROL for the section above: identical board, Shifty Suspects never attacks, so no lock is set
#// and Iden's heal lands (3 → 2). Without this, a heal that had failed for an unrelated reason —
#// Iden's condition unmet, the leader exhausted, the action never reaching her — would read as the
#// far-seat lock working.

## GIVEN
CommonSetup3P: bbk/bbk/rrw/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 1
WithP3GroundArena: LAW_197:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2

---

# Seat2_ShiftySuspects_LocksHealingForSeat1
#// The GAME-1310526 case, and the one seat pair the old `seat 1 || seat 2` gate already covered —
#// kept as the regression that the fix must not change. Seat 2 attacks seat 3's base with Shifty
#// Suspects; P1's Iden then heals nothing. This is why the live report "an enemy unit was defeated
#// this phase but Iden healed nothing" was correct play, not a bug.

## GIVEN
CommonSetup3P: bbk/rrw/bbk/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: LAW_197:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P2>AttackGroundArena:0:P3B
- P3>Pass
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3

---

# FarSeat3_ConfederateTriFighter_LocksHealingForSeat1
#// RED before the fix. The CONTINUOUS locker, which is a separate scan from the phase flag:
#// TWI_132 Confederate Tri-Fighter's "Bases can't be healed." names no side and no controller, so
#// one copy anywhere on the table stops every base heal in the game. _SWUBasesCantBeHealed() only
#// looked at seats 1 and 2, so a seat-3 Tri-Fighter locked nothing. No attack is needed — the lock
#// is on the card being in play.

## GIVEN
CommonSetup3P: bbk/bbk/bbk/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 1
WithP3SpaceArena: TWI_132:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3

---

# FarSeat3_GeneralGrievous_LocksHealingForSeat1
#// RED before the fix. The second continuous locker, HMW_159 General Grievous (Scourge of
#// Dathomir), on the GROUND at seat 3. Both entries in the locker list are covered so that fixing
#// the scan for one of them cannot leave the other behind.

## GIVEN
CommonSetup3P: bbk/bbk/bbk/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 1
WithP3GroundArena: HMW_159:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3

---

# FarSeat4_ShiftySuspects_LocksHealingForSeat1
#// RED before the fix. Four seats: the lock comes from seat 4, the seat furthest from the old
#// hard-coded pair. A fix that widened the scan to "1, 2 or 3" would pass every section above and
#// fail here.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/rrw/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 4
WithP4GroundArena: LAW_197:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P4>AttackGroundArena:0:P2B
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
