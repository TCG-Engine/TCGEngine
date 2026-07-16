# WhenDefeatedOppReadyResource
#// TS26_076 Wartime Profiteer (Ground, 3/3) — When Defeated: each opponent may ready a resource.
#// Profiteer (pre-damaged to 1 HP) attacks LAW_124 and dies to the counter; P2 (1 exhausted resource)
#// readies it → P2 ready resources 2 → 3.
## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_076:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0
## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES
## EXPECT
P2RESAVAILABLE:3
