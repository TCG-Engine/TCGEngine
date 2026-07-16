# Action_BounceSelf_BuffUnit
#// SEC_093 C-3PO (Ground, 1/3) — Action [Exhaust, return this unit to its owner's hand]: Give a unit
#//   +2/+2 for this phase. C-3PO (idx 0) returns to hand; the only remaining unit SEC_041 (1/4 → 3/6)
#//   auto-resolves as the +2/+2 target.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_093:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
