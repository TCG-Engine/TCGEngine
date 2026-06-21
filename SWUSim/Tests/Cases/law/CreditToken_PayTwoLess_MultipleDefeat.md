# Credit token core — defeating MULTIPLE Credit tokens in one payment. P1 has 3 resources + 2 Credit
#   tokens (at myResources-3 and myResources-4). Playing SOR_063 (cost 3, Vigilance) and defeating both
#   tokens pays 2 less → only 1 resource exhausted. Exercises the batch-mark-then-cleanup path so the
#   second token's mzID isn't invalidated by reindexing when the first is defeated.

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
