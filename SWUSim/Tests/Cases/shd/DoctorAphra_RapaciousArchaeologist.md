# Deployed_FiveCostsBuff
#// SHD_015 Doctor Aphra (Leader, deployed unit 2/5) — deployed passive:
#//   "While there are 5 or more different costs among cards in your discard pile, this unit gets +3/+0."
#// Discard holds 5 cards with distinct costs (1,2,3,4,8) → the deployed Aphra unit is 5 power (2+3), HP 5.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015:1:1}
P1OnlyActions: true
WithP1Discard: [SOR_128 SEC_080 SOR_063 SOR_046 LAW_124]

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_015
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# Deployed_FourCostsNoBuff
#// SHD_015 Doctor Aphra — only 4 different costs in discard (1,2,3,4) → no +3/+0 (power stays 2).

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015:1:1}
P1OnlyActions: true
WithP1Discard: [SOR_128 SEC_080 SOR_063 SOR_046]

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_015
P1GROUNDARENAUNIT:0:POWER:2

---

# Front_RegroupMill
#// SHD_015 Doctor Aphra (leader FRONT side, undeployed) — "When the regroup phase starts: Discard a card
#//   from your deck." Both players pass to reach regroup; at RegroupPhaseStart Aphra mills 1 (deck→discard,
#//   From:DECK) before the draw step. Deck 6 → -1 mill -2 regroup-draw = 3 left; discard holds the 1 milled.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015}
WithActivePlayer: 1
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1DISCARDCOUNT:1
P1DECKCOUNT:3

---

# WhenDeployed_ReturnOneAtRandom
#// SHD_015 Doctor Aphra — When Deployed: "Choose 3 cards in your discard pile with different names. If you
#//   do, return 1 of them at random to your hand." Discard has 3 distinct-named cards; P1 deploys (5+
#//   resources) and picks all 3. Exactly one returns to hand at random: hand 0→1, discard 3→2. (Which card
#//   returns is random, so only the aggregate counts are asserted.)

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
