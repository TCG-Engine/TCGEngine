# SHD_015 Doctor Aphra — When Deployed: "Choose 3 cards in your discard pile with different names. If you
#   do, return 1 of them at random to your hand." Discard has 3 distinct-named cards; P1 deploys (5+
#   resources) and picks all 3. Exactly one returns to hand at random: hand 0→1, discard 3→2. (Which card
#   returns is random, so only the aggregate counts are asserted.)

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015;myResources:5}
P1OnlyActions: true
WithP1Discard: [SOR_128 SEC_080 SOR_063]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0&myDiscard-1&myDiscard-2

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:1
P1DISCARDCOUNT:2
