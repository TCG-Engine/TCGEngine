# SHD_016 Fennec Shand (deployed Action) — same play-≤4 + Ambush grant. Deployed (5 resources), the
# deployed Action plays SOR_229 (cost 2) at index 1 with Ambush (no enemy units → Ambush attack skipped).

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
