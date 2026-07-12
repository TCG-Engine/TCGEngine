# TWI_181 Elite P-38 Starfighter (Unit 3/2, Space, cost 3, Cunning/Villainy, Separatist/Vehicle/Fighter) —
# "When Played/When Defeated: You may deal 1 damage to a unit." Playing it, the option deals 1 to the enemy
# SOR_046. Base y + leader yk cover both Cunning/Villainy pips.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3;handCardIds:TWI_181}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_181
P2GROUNDARENAUNIT:0:DAMAGE:1
