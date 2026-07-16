# OnAttack_ReadyResourcePerDefeated
#// TWI_186 San Hill (Unit 3/7, Ground, cost 6) — "Exploit 3. On Attack: For each friendly unit that was
#// defeated this phase, ready a friendly resource." SOR_128 (3/1) attacks SOR_046 and dies (1 friendly
#// defeated this phase). Then San Hill attacks P2's base; its On Attack readies 1 exhausted resource
#// (P1 had 2 exhausted → 1 becomes ready).

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_186:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Resources: 2:SOR_046:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:1
