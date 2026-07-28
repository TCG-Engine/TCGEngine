# OnAttackPayCredit
#// LAW_258 Criminal Contact (1/4) — On Attack: you may pay 2 resources. If you do, create a Credit
#// token. Attacks the base; pay 2 -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LAW_258:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:0

---

# OnAttackDeclineNoCredit
#// LAW_258 Criminal Contact (1/4) — On Attack: you may pay 2 resources to create a Credit token. Declining
#// the "you may" leaves resources unspent and creates no Credit.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LAW_258:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1CREDITCOUNT:0
P1RESAVAILABLE:2

---

# OnAttackCannotPayTwoNoTrigger
#// LAW_258 Criminal Contact (1/4) — On Attack: the pay-2-resources ability does not trigger when the player
#// only has 1 resource. Attacks the base with 1 resource; no Credit is created and the resource is unspent.

## GIVEN
CommonSetup: yyk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_258:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
P1RESAVAILABLE:1
