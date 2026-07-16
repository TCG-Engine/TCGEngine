# WhenDefeated_OppReadyResource
#// SEC_215 Emissary's Sheathipede (Space, 2/4) — When Defeated: each opponent may ready a resource.
#//   SEC_215 (pre-damaged to 1 HP) attacks SOR_237 and dies to the counter; P2 (1 exhausted resource)
#//   chooses to ready it → P2 ready resources 2 → 3.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_215:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P2RESAVAILABLE:3
