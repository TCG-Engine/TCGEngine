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
