# PLOT (CR §19) — SEC_070 Armor of Fortune (Upgrade, Plot, cost 2, Vigilance, +0/+3)
# Proves the upgrade branch of Plot: "When you deploy a leader, you may play this card
# from your resources, paying its cost. Replace it with the top card of your deck."
#
# P1 controls SEC_070 as a resource (myResources-0) plus 5 vanilla resources (6 total ready
# — also satisfies Iden's deploy threshold of 6). A friendly Battlefield Marine (SOR_095,
# 3/3) is in the ground arena as a host. P1 deploys Iden (bk leader covers Vigilance → no
# aspect penalty, SEC_070 stays cost 2).
#
# After deploy the Plot window offers SEC_070 (the only affordable Plot resource). P1 plays it:
#   • MZMAYCHOOSE the Plot resource → myResources-0
#   • upgrade host prompt (2 units: marine + leader unit) → attach to the marine (myGroundArena-0)
#   • cost 2 paid (the Plot card may exhaust itself, like Smuggle) → ready resources 6 → 4
#   • SEC_070 leaves resources; its slot is replaced by the top of the deck (exhausted)
# Net: marine gains +0/+3 (3/6) with one upgrade; resource COUNT unchanged (replacement),
# ready resources drop by 2, deck shrinks by 1.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1
WithP1Resources: 1:SEC_070:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SEC_070
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
P1RESCOUNT:6
P1RESAVAILABLE:4
P1DECKCOUNT:1
P1HANDCOUNT:0
P1NODECISION
