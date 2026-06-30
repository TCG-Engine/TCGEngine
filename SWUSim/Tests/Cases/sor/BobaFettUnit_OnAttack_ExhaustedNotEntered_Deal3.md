# SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
# deal 3 to the defender. Boba (3/5) attacks a seeded exhausted SOR_046 (3/7, not played this round):
# OnAttack deals 3, then combat adds 3 → 6 total. SOR_046 survives (7 HP); Boba takes 3 counter.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3
