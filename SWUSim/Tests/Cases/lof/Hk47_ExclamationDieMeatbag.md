# EnemyDefeated_BaseDamage
#// LOF_130 HK-47 (2/4) — "When an enemy unit is defeated: deal 1 damage to its controller's base." HK-47
#// attacks and defeats the enemy 3/1; on its defeat, HK-47 deals 1 to P2's base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
