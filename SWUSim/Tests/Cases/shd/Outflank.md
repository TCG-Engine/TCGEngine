# Outflank_AttackWithTwoUnits
#// SHD_128 Outflank — "Attack with 2 units (one at a time)." P1 has two ready ground units (SOR_046 3
#// power, SOR_095 3 power) and P2 has no units, so both attacks hit the base: 3 + 3 = 6. Only the first
#// attacker is chosen; the second auto-resolves (lone remaining unit, base-only).

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_128
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
