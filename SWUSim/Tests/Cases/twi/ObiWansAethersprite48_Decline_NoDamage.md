# TWI_048 Obi-Wan's Aethersprite — the "may" is optional: declining the choose (AnswerDecision:-) deals
# no damage to itself or the enemy space unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:TWI_048}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_048
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
