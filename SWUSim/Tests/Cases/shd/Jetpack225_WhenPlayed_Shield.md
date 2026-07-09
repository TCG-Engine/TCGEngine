# SHD_225 Jetpack (+2/+0, non-Vehicle) — "When Played: Give a Shield token to attached unit."
# Attached to the marine (single host → auto): 5/3 with 1 shield + the Jetpack (UPGRADECOUNT 2).

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
