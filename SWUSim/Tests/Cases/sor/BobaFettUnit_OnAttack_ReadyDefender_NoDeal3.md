# SOR_179 Boba Fett — condition gate: the defender must be EXHAUSTED. Attacking a READY SOR_046 →
# OnAttack does NOT deal 3; only combat damage (3) lands.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
