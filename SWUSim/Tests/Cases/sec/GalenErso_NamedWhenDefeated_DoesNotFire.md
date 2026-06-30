# SEC_046 Galen Erso — naming a card denies its When Defeated ability. SEC_132 Imperial Occupier's
# "When Defeated: Create a Spy token" should not fire. P1 names "Imperial Occupier"; P2's SEC_132 (2/2)
# attacks an 8/8 (SOR_039) and dies, but no Spy is created — so P2's board is empty afterward.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_039:1:0
WithP2GroundArena: SEC_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Imperial Occupier
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
