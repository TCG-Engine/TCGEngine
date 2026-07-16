# NoLock_RestoreHeals
#// SOR_160 Wolffe — control test: WITHOUT Wolffe's lock, the Restore 1 unit heals P1's base normally
#// (3 → 2), proving the lock (not a broken Restore) is what blocks it in the other test.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# OnAttack_LocksBaseHeal
#// SOR_160 Wolffe — the lock also triggers On Attack. Wolffe (in play) attacks the enemy base, setting
#// the lock; then the Restore 1 unit (SOR_044) attacks and its Restore heal is blocked (base stays 3).
#// Base takes Wolffe's 3 + SOR_044's 2 = 5.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:5

---

# WhenPlayed_LocksBaseHeal
#// SOR_160 Wolffe (Aggression unit, cost 2, 3/2, Fringe/Clone) — "Saboteur. When Played/On Attack:
#// Bases can't be healed for this phase." P1's base is at 3 damage. Playing Wolffe locks base healing,
#// so when the Restore 1 unit (SOR_044) attacks, its Restore heal is blocked and the base stays at 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP1Hand: SOR_160

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:2
