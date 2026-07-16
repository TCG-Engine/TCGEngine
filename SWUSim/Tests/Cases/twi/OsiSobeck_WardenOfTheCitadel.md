# ExploitOne_CapturesCheaperOnly
#// TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3, cost 6 (Command).
#// Test: Exploit 3 defeats 1 friendly SEC_080 → effective cost = 4, paid = 4.
#// P2 has two ground units: SOR_035 (Lieutenant Childsen, cost 4 ≤ 4, eligible) and
#// SOR_067 (Rugged Survivors, cost 5 > 4, ineligible). Only SOR_035 is offered.
#// SWUQueueChooseTarget auto-picks SOR_035 (PASSPARAMETER, only 1 eligible).
#// TWI115_CAPTURE fires: SOR_035 is captured by TWI_115.
#// Assertions: P2 ground arena count = 1 (SOR_067 stays); P2GROUNDARENAUNIT:0:CARDID:SOR_067.
#//             TWI_115 has 1 subcard = the captured SOR_035 (UPGRADECOUNT:1).
#// Resources: 8 ready; cost = 4 after 1 Exploit defeat → 4 remain.
#// Leader: ggk (Echo Base + Tarkin). Covers Command aspect; no aspect penalty.

## GIVEN
CommonSetup: ggk/grw/{myResources:8;handCardIds:TWI_115}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_035:1:0
WithP2GroundArena: SOR_067:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_115
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_035
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_067
P1RESAVAILABLE:4

---

# ExploitToZero_NoCapture
#// TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3, cost 6 (Command).
#// Test: Exploit 3 defeats 3 friendly SEC_080 fodder units → effective cost = 0, paid = 0.
#// WhenPlayed: eligible = [] (nothing has cost ≤ 0 in P2's ground arena). Ability fizzles.
#// P2's SOR_095 (cost 2) is present but cost 2 > 0 → ineligible. Unit stays.
#// Assertions: P2 ground arena count stays at 1; TWI_115 has no subcards (UPGRADECOUNT:0).
#// P1 arena: 3 fodder units defeated → only TWI_115 remains (count = 1).
#// Resources: 8 ready; cost = max(0, 6 - 6) = 0 → 8 remain after free play.
#// Leader: ggk (Echo Base + Tarkin). Tarkin covers Command aspect; no aspect penalty.

## GIVEN
CommonSetup: ggk/grw/{myResources:8;handCardIds:TWI_115}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_115
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:8

---

# PaidSix_CapturesCostSixOrLess
#// TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3, cost 6 (Command).
#// Test: When Played fires, paid = 6 (full cost, no Exploit), captures enemy non-leader ground unit
#// with cost ≤ 6. P2 has one SOR_095 Battlefield Marine (cost 2 ≤ 6) in the ground arena.
#// P1 declines Exploit (selects 0 units → "-"). TWI_115 enters with SWU_PAID_6 stamp.
#// WhenPlayed: eligible = [SOR_095] (cost 2 ≤ 6). SWUQueueChooseTarget auto-picks the only
#// target (PASSPARAMETER). TWI115_CAPTURE fires: DoCaptureUnit removes SOR_095 from P2's ground
#// arena and adds it as an IsCaptive subcard on TWI_115.
#// Assertions: P2 ground arena is empty; TWI_115 has 1 subcard (the captured SOR_095).
#// Leader: ggk (Echo Base + Tarkin). Tarkin covers Command aspect; no aspect penalty.
#// Resources: 6 ready → 0 remaining after paying full cost.

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
