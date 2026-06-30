# TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3, cost 6 (Command).
# Test: When Played fires, paid = 6 (full cost, no Exploit), captures enemy non-leader ground unit
# with cost ≤ 6. P2 has one SOR_095 Battlefield Marine (cost 2 ≤ 6) in the ground arena.
# P1 declines Exploit (selects 0 units → "-"). TWI_115 enters with SWU_PAID_6 stamp.
# WhenPlayed: eligible = [SOR_095] (cost 2 ≤ 6). SWUQueueChooseTarget auto-picks the only
# target (PASSPARAMETER). TWI115_CAPTURE fires: DoCaptureUnit removes SOR_095 from P2's ground
# arena and adds it as an IsCaptive subcard on TWI_115.
# Assertions: P2 ground arena is empty; TWI_115 has 1 subcard (the captured SOR_095).
# Leader: ggk (Echo Base + Tarkin). Tarkin covers Command aspect; no aspect penalty.
# Resources: 6 ready → 0 remaining after paying full cost.

## GIVEN
CommonSetup: ggk/grw/{myResources:6;handCardIds:TWI_115}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_115
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
