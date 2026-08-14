# OnAttackPayCredit
#// LAW_258 Criminal Contact (1/4) — On Attack: you may pay 2 resources. If you do, create a Credit
#// token. Attacks the base; pay 2 -> 1 Credit.
#// COVERAGE: offer=N/A (YES/NO pass-ability prompt, no target pool) · decline=OnAttackDeclineNoCredit ·
#//           boundary pair=OnAttackCannotPayTwoNoTrigger (1 resource — unaffordable, never offered) +
#//           OnAttackPayWithResourceAndCredit (exactly affordable once the Credit counts) ·
#//           control=N/A (one-shot attack trigger, no persistent per-unit marker) ·
#//           reqboundary=OnAttackPayWithResourceAndCredit (the YES and the Credit pick arrive on
#//           separate requests; the pending payment survives the round-trip)

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

---

# OnAttackPayWithResourceAndCredit
#// LAW_258 Criminal Contact — the pay-2 cost can be paid partly with a Credit token (CR 3.13: defeat the
#// token to pay 1 less). P1 has 1 ready resource + 1 Credit: the ability IS offered (the Credit counts
#// toward affordability), P1 accepts, defeats the Credit + spends the 1 resource, and the ability creates
#// a fresh Credit — ending at 1 Credit, 0 ready resources.

## GIVEN
CommonSetup: yyk/bgw/{myResources:1}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: LAW_258:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myResources-1

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:0
