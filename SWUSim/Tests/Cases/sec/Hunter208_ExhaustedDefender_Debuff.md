# SEC_208 Hunter (Ground, 7/6) — Saboteur + On Attack: if the defender is exhausted, it gets -4/-0 for
#   this attack. Attacks an EXHAUSTED SOR_046 → its counter power 3-4=0 → Hunter takes 0.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_208:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
P1NODECISION
