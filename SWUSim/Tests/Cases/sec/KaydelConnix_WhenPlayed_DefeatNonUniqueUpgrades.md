# SEC_149 Kaydel Connix (Unit, Aggression/Heroism, cost 3) — When Played: you may defeat all non-unique
#   upgrades on a unit. (Plot keyword dormant from hand.) SOR_095 bears SOR_120 (non-unique) → defeated.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_149

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
