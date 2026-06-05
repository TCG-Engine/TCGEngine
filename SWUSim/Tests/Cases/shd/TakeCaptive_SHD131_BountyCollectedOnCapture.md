# SHD_131 Take Captive — Bounty is collected on capture (CR 7.6.3).
# P2's unit is SHD_027 Hylobon Enforcer (Bounty: Draw a card).
# P1 captures SHD_027 via SHD_131; CollectCaptureTriggers offers Bounty to P1 (the capturing player).
# P1 answers YES to collect → draws 1 card from deck (SOR_095 placed in P1's deck).
# P1 started with 0 cards in hand after playing SHD_131 (event goes to discard) + 1 drawn from deck.
# Both capturer (P1's SOR_095) and captive (P2's SHD_027) are auto-picked (single eligible each step).
# Assertions: capture happened (SHD_027 subcard on captor); P1 drew 1 card (P1HANDCOUNT:1).
# Resources: 3 ready → 0 after paying cost 3.
# Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_027:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_027
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1HANDCOUNT:1
