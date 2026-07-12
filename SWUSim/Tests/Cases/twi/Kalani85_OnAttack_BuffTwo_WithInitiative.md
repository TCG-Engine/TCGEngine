# TWI_085 Kalani — WITH the initiative, Kalani may buff up to 2 other units. Attacking P2's base, she
# gives both SOR_095 and SEC_080 +2/+2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: TWI_085:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:2:POWER:5
