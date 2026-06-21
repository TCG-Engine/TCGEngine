# SEC_253 Covert Operative (Ground, 2/4, Heroism, cost 4) — When Played: this unit captures an enemy
#   non-leader unit that costs 2 or less. Captures SOR_128 (cost 1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_253

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_253
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
