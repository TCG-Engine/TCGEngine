# SHD_013 Han Solo (deployed Action) — same play-discounted + deal-2. Deployed (5 resources), the
# deployed Action plays SOR_229 (cost 3 → 2) at index 1 and deals it 2.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:DAMAGE:2
