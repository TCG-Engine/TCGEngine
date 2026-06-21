# SEC_096 Ahsoka Tano — decline the attack-end disclose → no second attack.
# Ahsoka attacks the base (2 damage); P1 declines (AnswerDecision:-), so SOR_095 stays READY and
# the base takes only 2.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_094

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1NODECISION
