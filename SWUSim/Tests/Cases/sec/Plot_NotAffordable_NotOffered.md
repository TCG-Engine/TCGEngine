# PLOT (CR §19) — affordability gate (PlayerHasPlotsToPlay)
# A Plot card the player cannot pay for is NOT offered when a leader deploys. Plot cost must
# be checked against READY resources (incl. aspect penalties).
#
# P1 controls SEC_036 (Plot, cost 6) at myResources-0, plus 2 ready and 3 exhausted vanilla
# resources = 6 controlled (meets Iden's deploy threshold) but only 3 READY. 3 < 6 → SEC_036
# is unaffordable → no Plot window appears. The leader deploys normally; SEC_036 stays a
# resource and the deck is untouched.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_036:1,2:SOR_095:1,3:SOR_095:0
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1RESCOUNT:6
P1RESAVAILABLE:3
P1DECKCOUNT:1
P1NODECISION
