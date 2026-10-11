#// CORE — a base heal stopped by a "bases can't be healed" lock gets its own log line.
#//
#// Reported as a bug from game 1310526 (2026-09-26): P1 used Iden Versio's Action with an enemy unit
#// defeated that phase and the base did not heal. That was CORRECT play — P2's LAW_197 Shifty Suspects
#// had attacked earlier in the phase — but `OnHealBase` returns silently, so the log showed only
#// "P1 used [[SOR_002|Iden Versio]]'s Action" with nothing after it, which reads exactly like the
#// ability misfiring. The ABSENCE of the HEAL line was the only tell, and no player is going to spot it.
#//
#// Two locks feed the same gate and both now name their source:
#//   • the phase flag SWU_NOHEAL_BASE — LAW_197 Shifty Suspects, SOR_160 Wolffe. The flag alone does not
#//     say which card set it, so each card stamps a companion `SWU_NOHEAL_BASE_SRC_<CardID>` marker
#//     (exact-match counting means it is invisible to the lock check, and the RegroupPhaseStart clear is
#//     by PREFIX so it is swept with the flag).
#//   • the continuous in-play lockers — TWI_132 Confederate Tri-Fighter, HMW_159 General Grievous.
#//
#// ⚠ Silence is the default here, so every "line appears" section needs a partner proving the line does
#// NOT appear when it shouldn't — otherwise a helper that fired on every heal would pass all of them.

# PhaseLock_ShiftySuspects_NamesTheCard
#// The reported case, reduced to two seats. P2 attacks P1's 3/7 with Shifty Suspects (setting the lock),
#// then P1 uses Iden's Action. Her condition is met by seeding P2 with SWU_FRIENDLY_DEFEATED (the flag
#// SOR_002 actually reads), so the section turns on the heal gate and not on a combat that could fail
#// for its own reasons. The base stays at 3 AND the log now says why.

## GIVEN
CommonSetup: bbk/rrw/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_197:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P2>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
LOGCONTAINS:P1's [[SOR_002|Iden Versio]] couldn't heal P1's base ([[LAW_197|Shifty Suspects]])
LOGCOUNT:1:couldn't heal
LOGCOUNT:0:healed 1 damage

---

# PhaseLock_Wolffe_NamesTheCard
#// The other phase-flag locker, SOR_160 Wolffe (When Played/On Attack). Both cards share one flag, so
#// without a section each they could be told apart only by reading the source marker code.

## GIVEN
CommonSetup: bbk/rrw/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P2>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
LOGCONTAINS:P1's [[SOR_002|Iden Versio]] couldn't heal P1's base ([[SOR_160|Wolffe]])
LOGCOUNT:1:couldn't heal

---

# ContinuousLock_TriFighter_NamesTheCard
#// The other gate: TWI_132 Confederate Tri-Fighter's printed "Bases can't be healed." No attack is
#// needed — the lock is on the card being in play — so this also proves the line does not depend on the
#// phase flag being set.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 1
WithP2SpaceArena: TWI_132:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
LOGCONTAINS:P1's [[SOR_002|Iden Versio]] couldn't heal P1's base ([[TWI_132|Confederate Tri-Fighter]])
LOGCOUNT:1:couldn't heal

---

# NoLock_HealsNormally_AndWritesNoBlockedLine
#// CONTROL. Identical to the Tri-Fighter section with the locker removed: the heal lands, the ordinary
#// HEAL line is written, and NO blocked line appears. Without this, a helper that logged on every call
#// to OnHealBase would satisfy all three sections above.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_002; myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 1
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2
LOGCONTAINS:P1's [[SOR_002|Iden Versio]] healed 1 damage from P1's base
LOGCOUNT:0:couldn't heal

---

# UndamagedBase_LockedButNothingToHeal_WritesNoLine
#// The other half of the silence rule. P1's base is at 0 damage, so the heal would have removed nothing
#// even unlocked — claiming the lock stopped it would be a lie, and on a full-health base with a
#// Tri-Fighter across the table it would fire on every Restore for the rest of the game.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_002; myBaseDamage:0}
SkipPreGame: true
WithActivePlayer: 1
WithP2SpaceArena: TWI_132:1:0
WithP2GlobalEffect: SWU_FRIENDLY_DEFEATED

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:0
LOGCOUNT:0:couldn't heal

---

# RestoreBlocked_WritesItsOwnLine_AboveTheAttackSummary
#// The Restore path, which is where most players will actually meet this line — and the one case with no
#// ability "source", so it takes the PASSIVE wording. P1's LAW_109 Tantive IV (Restore 2) attacks P2's
#// base while P1's own Shifty Suspects holds the lock (it locks every base, including its controller's).
#// The line is a standalone entry printed ABOVE the ATTACK summary, not a note appended to it: base
#// heals never run inside the combat-damage log window that the prevention helpers split on, so there is
#// nothing to append to yet. That ordering matches the standing ruling on On Attack effect lines.
#// LASTLOGCONTAINS pins it — if a future change starts folding this into the ATTACK line, this fails.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
LOGCONTAINS:P1's base couldn't be healed ([[LAW_197|Shifty Suspects]])
LOGCOUNT:1:couldn't be healed
LASTLOGCONTAINS:P1's [[LAW_109|Tantive IV]] attacked P2's base for 5 damage
