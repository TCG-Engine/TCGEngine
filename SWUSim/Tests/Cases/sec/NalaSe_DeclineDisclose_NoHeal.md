# SEC_065 Nala Se — decline the optional disclose → no heal happens (the attack still lands).
# Same board as the positive test; P1 declines the disclose (AnswerDecision:-) so the "if you do"
# heal never offers and SOR_046 keeps its 4 damage.

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP1GroundArena: SOR_046:1:4
WithP1Hand: SEC_054

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:DAMAGE:4
P1HANDCOUNT:1
P1NODECISION
