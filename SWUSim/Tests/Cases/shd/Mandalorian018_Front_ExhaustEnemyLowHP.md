# SHD_018 The Mandalorian (front, undeployed) — "When you play an upgrade: You may exhaust this leader.
# If you do, exhaust an enemy unit with 4 or less remaining HP." P1 plays SOR_069 (upgrade) onto its
# SOR_046, accepts (exhausting The Mandalorian), and exhausts the enemy SOR_160 (2 HP).

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_018}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_069
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
