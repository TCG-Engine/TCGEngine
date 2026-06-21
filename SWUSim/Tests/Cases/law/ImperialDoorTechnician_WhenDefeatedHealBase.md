# LAW_097 Imperial Door Technician (2/2) — When Defeated: heal 2 damage from your base. Attacks SOR_046
# (3/7) and dies; P1's base (damaged 2) heals to 0.

## GIVEN
CommonSetup: brk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:0
