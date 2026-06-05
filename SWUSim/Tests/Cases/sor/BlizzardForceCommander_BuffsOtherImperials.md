# SOR_230 Blizzard Force Commander / General Veers (3/3, Imperial) —
# "Other friendly Imperial units get +1/+1." The OTHER Imperial unit (Death
# Trooper SOR_033, 3/3) reads 4/4; Veers himself is excluded ("other") → stays 3/3.

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers (3/3, Imperial) — index 0
WithP1GroundArena: SOR_033:1:0    # Death Trooper (3/3, Imperial) — index 1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
