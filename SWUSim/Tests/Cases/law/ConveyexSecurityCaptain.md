# DisablesEnemyCreditPayment
#// LAW_117 Conveyex Security Captain (Unit, cost 3, Vigilance, 2/4) — "Enemy Credit tokens lose all
#//   abilities." P2 controls LAW_117, so P1's Credit token loses its "defeat to pay 1 less" ability:
#//   no credit-payment offer appears when P1 plays a card, and P1 must pay the full cost in resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP2GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# DoesNotAffectFriendlyCredits
#// LAW_117 Conveyex Security Captain — its constant ability only blanks ENEMY Credit tokens. When P1
#// controls Conveyex, P1's OWN Credit token keeps its "defeat to pay 1 less" ability. P1 plays SOR_095
#// (cost 2, Command/Heroism) with 2 resources + 1 friendly Credit, defeats the Credit to pay 1 less →
#// only 1 resource is exhausted and the Credit is gone.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP1GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-2

## EXPECT
P1GROUNDARENACOUNT:2
P1CREDITCOUNT:0
P1RESAVAILABLE:1
P1NODECISION
