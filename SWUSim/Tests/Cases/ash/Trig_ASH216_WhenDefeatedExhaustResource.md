# ASH_216 Mandalorian Scout (Ground, 3/3, cost 2) — When Defeated: exhaust a ready friendly resource. The
# Scout attacks SOR_046 (3/7) and dies to the counter; its WhenDefeated exhausts one of P1's 3 ready
# resources (3 → 2 ready).
## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:2
