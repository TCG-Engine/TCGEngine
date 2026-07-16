# BuffsHeroismUnits
#// TWI_092 Admiral Yularen (Unit 2/5, Ground) — "Restore 1. Each other friendly Heroism unit gets
#// +0/+1." A friendly Heroism unit (SOR_095, 3/3) gets +0/+1 → 3/4; a Villainy filler (SEC_080) is
#// unaffected. Yularen himself (self-excluded) stays 2/5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_092:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:2:HP:3
P1GROUNDARENAUNIT:0:HP:5
