# ASH_127 The Twins — "When another friendly unit is defeated: heal 1 from your base." P1's base starts
# at 2 damage; the friendly SOR_128 (3/1) attacks SEC_080 (3/3) and dies to the counter — a friendly unit
# was defeated, so The Twins heals 1 from the base (2 → 1).
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2}
WithP1GroundArena: ASH_127:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:1
