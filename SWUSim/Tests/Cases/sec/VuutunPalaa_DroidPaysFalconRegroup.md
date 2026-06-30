# SEC_122 Vuutun Palaa -- Droid pays the Falcon regroup cost (non-play cost coverage)
# "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
# SOR_193 Millennium Falcon is in the Space arena. At the regroup phase Ready step the
# Falcon trigger fires and asks the controller to pay 1 resource or bounce. Because
# SEC_122 is in play and a ready Droid (SOR_236 R2-D2) is on the board, the engine
# offers a MZMULTICHOOSE instead of an immediate resource payment. P1 picks the Droid
# and the Droid is exhausted, NO resources are spent (P1RESAVAILABLE stays at 0),
# and the Falcon STAYS in the Space arena.
#
# Space arena order: SEC_122 (index 0), SOR_193 (index 1).
# Ground arena: SOR_236 R2-D2 at index 0 (the only ready Droid offered).
# SOR_236 placed directly via WithP1GroundArena (no WhenPlayed trigger fires).
#
# Phase flow (mirrors existing Falcon regroup tests):
#   P1>Pass                           - P1 passes main action
#   P1>ResourcePass / P2>ResourcePass - both answer the Resource-step MZMAYCHOOSE
#   P1>AnswerDecision:YES             - keep the Falcon (SEC_122 triggers MZMULTICHOOSE)
#   P1>AnswerDecision:myGroundArena-0 - exhaust R2-D2 (FALCON_DROIDPAY_RESOLVE)

## GIVEN
CommonSetup: ygw/yrk
P1OnlyActions: true
WithP1SpaceArena: SEC_122
WithP1SpaceArena: SOR_193
WithP1GroundArena: SOR_236
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SOR_193
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_236
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:0
P1RESAVAILABLE:0
