# SHD_006 Jabba the Hutt — "When Deployed: Another friendly unit captures an enemy non-leader unit."
# P1 deploys Jabba (Epic Action, 7+ resources). On deploy, P1's Industrious Team (LAW_124) — the only
# friendly non-Jabba unit — captures the enemy Battlefield Marine (SOR_095), the only enemy non-leader
# unit. Both picks auto-resolve. The marine leaves P2's arena (captured as a face-down subcard on LAW_124).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
