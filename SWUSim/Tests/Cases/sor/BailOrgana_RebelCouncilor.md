# ActionGivesExp
#// SOR_094 Bail Organa (1/?) — Action [Exhaust]: give an Experience token to another
#// friendly unit. P1 uses Bail's action; his only other friendly (Battlefield Marine,
#// 3/3) is the sole target → auto-receives +1/+1 (→ 4/4). Bail is exhausted.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0    # Bail Organa (ready) — index 0
WithP1GroundArena: SOR_095:1:0    # the other friendly unit — index 1

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:0:EXHAUSTED
