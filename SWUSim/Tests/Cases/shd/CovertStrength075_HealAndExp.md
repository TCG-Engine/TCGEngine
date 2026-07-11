# SHD_075 Covert Strength (1-cost event) — "Heal 2 damage from a unit and give an Experience token
# to it." Single friendly target (2-damaged marine) → auto-resolve: damage 0, +1 Experience → 4/4.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_075
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1DISCARDCOUNT:1
