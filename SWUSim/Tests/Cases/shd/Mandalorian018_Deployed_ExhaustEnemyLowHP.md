# SHD_018 The Mandalorian (deployed) — "When you play an upgrade: You may exhaust an enemy unit with 6
# or less remaining HP." (No leader-exhaust cost.) Deployed as a unit, P1 plays SOR_069 (auto-attaches to
# the deployed leader — its only unit) and exhausts the enemy SOR_014 (5 HP — a valid ≤6 target that the
# front side's ≤4 would not reach).

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_018;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_069
WithP2GroundArena: SOR_014:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
