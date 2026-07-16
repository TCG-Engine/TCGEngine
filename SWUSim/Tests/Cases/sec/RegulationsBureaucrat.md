# Action_ExhaustResource
#// SEC_216 Regulations Bureaucrat (Ground, 0/5) — Action [Exhaust]: exhaust a resource (an opponent's).
#//   P1 uses the ability: SEC_216 exhausts itself and exhausts one of P2's 3 ready resources → P2 has 2.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP2Resources: 3:SOR_046:1

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2RESAVAILABLE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
