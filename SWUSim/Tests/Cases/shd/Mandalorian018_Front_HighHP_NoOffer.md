# SHD_018 The Mandalorian (front) — the front side only reaches enemies with 4 or less remaining HP. With
# the sole enemy at 5 HP (SOR_014), no offer is made: The Mandalorian stays ready and nothing is exhausted.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_018}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_069
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_014:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:READY
