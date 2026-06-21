# SEC_221 Unruly Astromech (Ground, 3/2) — Hidden + When Defeated: exhaust an enemy unit. SEC_221
#   attacks SOR_046 (3/7) and dies to the counter; on defeat the only enemy (SOR_046) is exhausted.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_221:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
