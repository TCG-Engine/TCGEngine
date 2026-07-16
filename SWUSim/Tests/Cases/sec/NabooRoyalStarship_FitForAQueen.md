# LeaderUnitGainsRaidOverwhelm
#// SEC_099 Naboo Royal Starship (Space, 2/5) — "Each friendly leader unit gains Raid 2 and Overwhelm."
#//   P1 deploys its leader (becomes a leader unit); with SEC_099 in play it has Raid and Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
P1OnlyActions: true
WithP1SpaceArena: SEC_099:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
