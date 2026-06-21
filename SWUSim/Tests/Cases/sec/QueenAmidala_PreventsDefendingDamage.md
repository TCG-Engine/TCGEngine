# SEC_101 Queen Amidala (5/3) — "If damage would be dealt to this unit, you may defeat another friendly
#   unit that shares a trait with this unit (Naboo/Official). If you do, prevent that damage." DEFENDING:
#   P2's Amidala is attacked by P1's SOR_046 (3 power) — she'd take 3 and die (3 HP). P2 defeats its
#   Official SEC_118 to prevent → Amidala takes 0 and survives; she counters 5 onto SOR_046.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:5
