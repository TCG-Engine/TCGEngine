# SEC_046 Galen Erso — naming "Experience" does NOT remove the stat bonus. An Experience token's +1/+1
# is a printed STAT, not an ability, so "loses all abilities" leaves it untouched. P2's SOR_095 (3/3)
# carries an Experience token (→ 4/4); after Galen names "Experience" it is still 4/4.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:4
