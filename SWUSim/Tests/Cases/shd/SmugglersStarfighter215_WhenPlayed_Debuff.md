# SHD_215 Smuggler's Starfighter (3-cost 2/2 space) — "When Played: If you control another
# Underworld unit, give an enemy unit -3/-0 for this phase." Synara San (Underworld) satisfies the
# gate; the sole enemy (Consular 3/7, single target → auto) drops to power 0 (floored).

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_215
WithP1GroundArena: SHD_033:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
