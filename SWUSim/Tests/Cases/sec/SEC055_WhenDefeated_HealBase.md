# SEC_055 Dhani Pilgrim — When Defeated: heal 1 damage from your base. SEC_055 (1/3) attacks SOR_046
#   (3/7) and dies to the counter; on defeat (within P1's own action) the base heals 1 (3 → 2).

## GIVEN
CommonSetup: bbk/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SEC_055:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
