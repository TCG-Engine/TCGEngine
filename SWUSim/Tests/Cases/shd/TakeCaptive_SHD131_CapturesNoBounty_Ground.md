# SHD_131 Take Captive — basic ground capture, no bounty.
# P1 plays Take Captive (Command/Tactic, cost 3).
# P1 has one friendly ground unit (SOR_095 Battlefield Marine, 3/3).
# P2 has one enemy ground unit (SOR_128 Death Star Stormtrooper, 1/3) — no Bounty keyword.
# Both choices are auto-picked (SWUQueueChooseTarget PASSPARAMETER: single eligible target each step).
# After capture: SOR_128 leaves P2's arena; becomes IsCaptive subcard on SOR_095.
# Assertions: P2 ground arena empty; P1 unit has UPGRADECOUNT 1; captive CardID = SOR_128.
# Resources: 3 ready — exact cost of SHD_131 → 0 remaining.
# Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered; no penalty).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_128
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
