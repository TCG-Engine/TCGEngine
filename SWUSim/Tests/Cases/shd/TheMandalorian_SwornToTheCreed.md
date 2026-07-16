# Deployed_ExhaustEnemyLowHP
#// SHD_018 The Mandalorian (deployed) — "When you play an upgrade: You may exhaust an enemy unit with 6
#// or less remaining HP." (No leader-exhaust cost.) Deployed as a unit, P1 plays SOR_069 (auto-attaches to
#// the deployed leader — its only unit) and exhausts the enemy SOR_014 (5 HP — a valid ≤6 target that the
#// front side's ≤4 would not reach).

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

---

# Front_ExhaustEnemyLowHP
#// SHD_018 The Mandalorian (front, undeployed) — "When you play an upgrade: You may exhaust this leader.
#// If you do, exhaust an enemy unit with 4 or less remaining HP." P1 plays SOR_069 (upgrade) onto its
#// SOR_046, accepts (exhausting The Mandalorian), and exhausts the enemy SOR_160 (2 HP).

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

---

# Front_HighHP_NoOffer
#// SHD_018 The Mandalorian (front) — the front side only reaches enemies with 4 or less remaining HP. With
#// the sole enemy at 5 HP (SOR_014), no offer is made: The Mandalorian stays ready and nothing is exhausted.

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
