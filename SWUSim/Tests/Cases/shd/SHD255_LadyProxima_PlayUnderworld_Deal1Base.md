# SHD_255 Lady Proxima — "When you play another Underworld card: You may deal 1 damage to a base." P1
# plays SHD_058 (an Underworld unit); Proxima deals 1 to the enemy base.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SHD_255:1:0
WithP1Hand: SHD_058

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
