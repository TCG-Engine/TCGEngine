# LOF_198 Stinger Mantis — When Played: may deal 2 damage to an exhausted unit. P1 deals 2 to the
# exhausted enemy 3/7.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:LOF_198}
P1OnlyActions: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
