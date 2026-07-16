# BuffsOtherRebels
#// SOR_242 Massassi Group Commander / General Dodonna (4/4, Rebel) —
#// "Other friendly Rebel units get +1/+1." The OTHER Rebel unit (Consular Security
#// Force SOR_046, 3/7) reads 4/8; Dodonna himself is excluded ("other") → stays 4/4.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_242:1:0    # General Dodonna (4/4, Rebel) — index 0
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7, Rebel) — index 1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:8
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
