# SEC_085 Vice Admiral Rampart — decline the optional disclose → no Experience tokens handed out.
# The fillers stay 3/3 (no token); the attack still lands.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_085:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SEC_080
WithP1Hand: SEC_080

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:POWER:3
P1NODECISION
