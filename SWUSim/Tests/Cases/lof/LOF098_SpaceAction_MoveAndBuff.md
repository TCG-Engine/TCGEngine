# LOF_098 — While in the space arena: "Action [use the Force]: Move this unit to the ground arena and
# give each friendly Heroism unit +2/+2 for this phase." LOF_098 (5/5 Heroism) sits exhausted in the
# space arena; using the Force moves her to the ground arena and buffs each friendly Heroism unit (her
# 5/5 → 7/7, the vanilla Heroism SOR_237 2/3 → 4/5). The action needs no exhaust (usable while tapped).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_237:1:0
WithP1SpaceArena: LOF_098:0:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:LOF_098
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
