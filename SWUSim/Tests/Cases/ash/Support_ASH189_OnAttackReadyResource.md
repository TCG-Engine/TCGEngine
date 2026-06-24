# ASH_189 Emperor's Messenger (Ground, 0/3, Support) — On Attack: ready a resource. P1 has 1 ready + 2
# exhausted resources; the Messenger attacks the enemy base and readies one exhausted resource (1 → 2).
## GIVEN
CommonSetup: yyk/yyk
WithP1Resources: 1:SOR_046:1,2:SOR_046:0
WithP1GroundArena: ASH_189:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1RESAVAILABLE:2
