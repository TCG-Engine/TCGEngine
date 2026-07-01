# Inline myLeader index-override (6th field): for a REGULAR-deploy leader unit (deployed=1), scoot the
# deployed leader in among the WithP1GroundArena units at the given index, shifting the rest up.
# myLeader:SOR_005:1:1:0:0:2 = ready, deployed-as-unit, epic 0, damage 0, index 2 — deploys SOR_005
# at index 2 into [SOR_095 ASH_048 SEC_098 LOF_045 LOF_096]:
#   → [SOR_095, ASH_048, SOR_005(leader), SEC_098, LOF_045, LOF_096]

## GIVEN
CommonSetup: ggw/ggw/{ myLeader:SOR_005:1:1:0:0:2 }
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 ASH_048:1:0 SEC_098:1:0 LOF_045:1:0 LOF_096:1:0]

## EXPECT
P1GROUNDARENACOUNT:6
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:ASH_048
P1GROUNDARENAUNIT:2:CARDID:SOR_005
P1GROUNDARENAUNIT:3:CARDID:SEC_098
P1GROUNDARENAUNIT:4:CARDID:LOF_045
P1GROUNDARENAUNIT:5:CARDID:LOF_096
