# SEC_101 Queen Amidala — ATTACKING: P1's Amidala (5/3) attacks P2's SEC_080 (3/3), defeats it, and would
#   take 3 counter (dying). P1 defeats its Official SEC_118 to prevent that counter → Amidala takes 0 and
#   survives. Proves the prevention covers damage the unit takes while attacking.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
