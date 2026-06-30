# SEC_162 Crosshair (Ground, 2/3) — On Attack: may deal 1 to another friendly unit; if you do, deal 2
#   to the defending player's base. Attacks P2 base (2 combat) → ping SOR_095 → +2 base = 4 total.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_162:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION
