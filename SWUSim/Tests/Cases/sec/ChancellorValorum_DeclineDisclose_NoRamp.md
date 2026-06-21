# SEC_107 Chancellor Valorum — decline the attack-end disclose → no ramp (resources/deck unchanged).

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_107:1:0
WithP1Hand: SEC_080
WithP1Hand: SEC_094
WithP1Hand: SEC_096
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1RESCOUNT:2
P1DECKCOUNT:2
P1NODECISION
