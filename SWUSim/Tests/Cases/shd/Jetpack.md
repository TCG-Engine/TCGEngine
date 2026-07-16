# TokenDefeatedAtRegroup
#// SHD_225 Jetpack — "At the start of the regroup phase, defeat that token." After the regroup the
#// shield is gone; the Jetpack itself stays attached (+2/+0 persists).

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5

---

# WhenPlayed_Shield
#// SHD_225 Jetpack (+2/+0, non-Vehicle) — "When Played: Give a Shield token to attached unit."
#// Attached to the marine (single host → auto): 5/3 with 1 shield + the Jetpack (UPGRADECOUNT 2).

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
