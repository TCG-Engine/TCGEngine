# DealsDamageEqualToCredits
#// LAW_247 Backed by the Hutts (Event, cost 3, Cunning) — Create a Credit token. You may deal damage
#//   to a unit equal to the number of friendly Credit tokens.
#//   P1 starts with 2 Credit tokens. Playing the event creates a 3rd FIRST, so the friendly count is 3
#//   when the damage resolves (proves create-then-count ordering, CR 3.13). 3 damage kills SEC_080 (3/3);
#//   a buggy count-before-create (2) would leave it alive.
#//   NOTE: with 2 usable Credit tokens in hand at play time, the credit-payment offer fires first — P1
#//   declines it (AnswerDecision:-), pays the 3 cost in resources, then the event resolves.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1Credits: 2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1CREDITCOUNT:3
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# DeclineDamage
#// LAW_247 Backed by the Hutts — the damage is optional ("You may"). P1 declines it; the credit is still
#//   created. (Credit-payment offer declined first, then the damage MZMAYCHOOSE declined.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1Credits: 2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
