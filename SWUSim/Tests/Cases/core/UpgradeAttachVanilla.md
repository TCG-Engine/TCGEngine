# Play SOR_120 (+2/+2) from hand onto SOR_095 → UPGRADECOUNT:1, correct POWER/HP
# SOR_120 has Command aspect; Leia (ggw) covers Command. Printed cost 2 + 0 penalty = 2 resources.

## GIVEN
CommonSetup: ggw/grw/{myResources:2;handCardIds:SOR_120}
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1RESAVAILABLE:0
