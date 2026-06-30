# SEC_035 Darth Sion (Ground, 5/5) — When Played: give an Experience token to him for each enemy unit
#   defeated this phase. P1's SOR_095 kills SOR_128 first (1 enemy defeated), then Darth Sion enters →
#   1 Experience → 6/6.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_035

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_035
P1GROUNDARENAUNIT:0:POWER:6
P1NODECISION
