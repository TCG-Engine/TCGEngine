# DeclinePayment_FullCost
#// Credit token core — defeating the token is optional. P1 declines (AnswerDecision:-), pays the full
#//   2-resource cost, and keeps the Credit token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# NotCountedForLeaderDeploy
#// Credit tokens are NOT resources — they do not count toward a leader's deploy threshold (CR 3.13).
#//   P1 controls Luke (SOR_005, deploy cost 6) with only 5 real resources + 3 Credit tokens. Total real
#//   resources (5) is below 6, so the deploy is unavailable even though 5+3=8 entries sit in the zone.
#//   Proves credits give ramp for paying costs but never earlier leader deployment.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Credits: 3

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:READY
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:3

---

# PayOneLess_ReducesCost
#// Credit token core (CR 3.13): "While paying resources, you may defeat this token. If you do, pay 1 less."
#//   P1 has 2 real resources + 1 Credit token (at myResources-2). P1 plays SOR_095 (cost 2, Command/Heroism)
#//   and defeats the Credit to pay 1 less — only 1 resource is exhausted, the Credit is gone.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:1
P1NODECISION

---

# PayTwoLess_MultipleDefeat
#// Credit token core — defeating MULTIPLE Credit tokens in one payment. P1 has 3 resources + 2 Credit
#//   tokens (at myResources-3 and myResources-4). Playing SOR_063 (cost 3, Vigilance) and defeating both
#//   tokens pays 2 less → only 1 resource exhausted. Exercises the batch-mark-then-cleanup path so the
#//   second token's mzID isn't invalidated by reindexing when the first is defeated.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_063
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-3&myResources-4

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1CREDITCOUNT:0
P1RESAVAILABLE:2
P1NODECISION
