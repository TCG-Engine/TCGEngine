# OnAttack_ReadiesResource
#// SOR_214 Smuggling Compartment (Upgrade) — Attach to a Vehicle unit. Attached unit gains:
#// "On Attack: Ready a resource." A Vehicle (Distant Patroller, SOR_060) carries the upgrade
#// and attacks the enemy base; the upgrade's On Attack readies P1's one exhausted resource
#// (ready resources 0 → 1). Exercises the upgrade-granted On Attack path (OnAttackFromUpgrade).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0          # Vehicle host (ready) — attacker, idx 0
WithP1SpaceArenaUpgrade: 0:SOR_214     # Smuggling Compartment attached
WithP1Resources: 1:SOR_095:0           # one EXHAUSTED resource → to be readied

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:1
P1RESCOUNT:1
