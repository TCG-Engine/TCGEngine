# HealBaseOnBaseHit
#// ASH_031 Hera Syndulla (Ground, 3/4) — When Attack Ends: if this unit dealt combat damage to a base,
#// heal that much damage from your base. P1's base starts at 3 damage; Hera attacks the enemy base (deals
#// 3), then heals 3 from her own base (3 → 0).
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_031:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:0
