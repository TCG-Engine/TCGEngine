# SHD_015 Doctor Aphra (Leader, deployed unit 2/5) — deployed passive:
#   "While there are 5 or more different costs among cards in your discard pile, this unit gets +3/+0."
# Discard holds 5 cards with distinct costs (1,2,3,4,8) → the deployed Aphra unit is 5 power (2+3), HP 5.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015:1:1}
P1OnlyActions: true
WithP1Discard: [SOR_128 SEC_080 SOR_063 SOR_046 LAW_124]

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_015
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
