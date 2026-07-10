# SHD_084 Phase-III Dark Trooper — "When combat damage is dealt to this unit: Give an Experience token
# to this unit (if it survives)." Dark Trooper (3/3) attacks SHD_095 (2/3): it deals 3 (kills SHD_095)
# and takes 2 counter-damage, surviving → gets an Experience token (→ 4/4 with 2 damage).

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_084:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:4
