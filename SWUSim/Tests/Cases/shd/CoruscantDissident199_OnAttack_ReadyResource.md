# SHD_199 Coruscant Dissident (3-cost ground) — "On Attack: You may ready a resource." Attacking the base,
# P1 readies its one exhausted resource (→ 1 ready).

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_199:1:0
WithP1Resources: 1:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESAVAILABLE:1
