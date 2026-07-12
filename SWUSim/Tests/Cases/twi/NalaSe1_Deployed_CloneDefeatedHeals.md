# TWI_001 Nala Se (Leader, deployed) — "Each friendly Clone unit gains: When Defeated: Heal 2 damage from
# your base." With Nala Se deployed and P1's base at 5, the Clone TWI_109 attacks SOR_046 (3/7) and dies to
# the counter, healing 2 from P1's base (5 → 3).
## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:5;myLeader:TWI_001:1:1}
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:3
