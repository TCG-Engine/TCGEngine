# WhenPlayed_DebuffAfterFriendlyDefeated
#// TWI_031 Rune Haako (Unit 3/2, Ground) — "When Played: If a friendly unit was defeated this phase, you
#// may give a unit -1/-1 for this phase." SOR_128 (3/1) attacks SOR_046 and dies (a friendly defeated
#// this phase); then Rune Haako is played and gives the enemy SOR_046 -1/-1 → power 2.

## GIVEN
CommonSetup: bbk/grw/{myResources:2;handCardIds:TWI_031}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
