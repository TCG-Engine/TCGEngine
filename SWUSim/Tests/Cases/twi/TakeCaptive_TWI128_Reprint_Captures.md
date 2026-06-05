# TWI_128 Take Captive (reprint of SHD_131) — verifies the reprint is wired to the same handler.
# Same setup as the SHD_131 basic capture test but using TWI_128.
# P1 plays TWI_128 (Command/Tactic, cost 3). One P1 ground unit (SOR_095); one P2 ground unit (SOR_128).
# Both choices auto-picked (PASSPARAMETER: single eligible target each step).
# Assertions: capture happened; P2 ground arena empty; captive is SOR_128 subcard on P1's unit.
# Resources: 3 ready → 0 remaining.
# Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered; no penalty).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:TWI_128}
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
