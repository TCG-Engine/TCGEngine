# SOR_196 Chewbacca — On Defense fires ONLY when HE is attacked.
# Absence guard: a SPACE combat elsewhere doesn't ready the exhausted Chewbacca sitting in the ground arena.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_196:0:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:EXHAUSTED
