# OnAttackNoBaseHeal
#// LAW_197 Shifty Suspects (4/5) — On Attack: bases can't be healed for this phase. Shifty attacks the
#// base (setting the lock); then Tantive IV (Restore 2) attacks the base but its Restore is blocked, so
#// P1's base (pre-damaged 3) stays at 3.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
