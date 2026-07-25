# Action_ExhaustEnemyResource
#// SEC_216 Regulations Bureaucrat (Ground, 0/5) — Action [Exhaust]: exhaust a resource. Printed "a
#//   resource" lets the controller choose which player's resource; choosing Opponent exhausts one of P2's
#//   3 ready resources → P2 has 2.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP2Resources: 3:SOR_046:1

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:Opponent

## EXPECT
P2RESAVAILABLE:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Action_ExhaustOwnResource
#// SEC_216 Regulations Bureaucrat — "a resource" (no qualifier) can be a FRIENDLY resource: choosing You
#//   exhausts one of the controller's own ready resources. P1 starts with 3 ready → 2 after (plus the
#//   Bureaucrat exhausts itself as the action cost).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP2Resources: 3:SOR_046:1

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:You

## EXPECT
P1RESAVAILABLE:2
P2RESAVAILABLE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
