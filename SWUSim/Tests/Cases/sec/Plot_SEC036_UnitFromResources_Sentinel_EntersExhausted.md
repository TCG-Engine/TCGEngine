# PLOT (CR §19) — SEC_036 Dogmatic Shock Squad (Unit, Sentinel + Plot, cost 6, Vigilance/Villainy)
# Proves the UNIT branch of Plot: a Plot unit played from resources deploys to the arena,
# enters exhausted (CR 19.c), keeps its keywords (Sentinel), and its resource slot is
# replaced by the top card of the deck.
#
# P1 controls SEC_036 as myResources-0 + 5 vanilla resources (6 ready — also meets Iden's
# deploy threshold). bk leader (Iden, Vigilance+Villainy) covers BOTH of SEC_036's aspects
# → no penalty, cost stays 6 (paid by exhausting all 6 ready resources, incl. the Plot card
# itself). After paying, SEC_036 leaves resources → replaced by top of deck (exhausted).
#
# SEC_036 has no When Played, so after it resolves the Plot window finds no more affordable
# snapshot Plots → no further decision.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_036:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_036
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:1
P1NODECISION
