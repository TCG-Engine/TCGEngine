# LOF_129 Acolyte of the Beyond — When Defeated half: the 2/3 Acolyte attacks a 3/3 unit and dies to the
# 3 counter-damage. Its When Defeated triggers → its controller (P1) gains the Force.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0
