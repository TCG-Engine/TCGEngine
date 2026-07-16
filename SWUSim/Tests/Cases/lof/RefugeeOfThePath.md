# ShieldSentinel
#// LOF_242 Refugee of The Path — When Played: may give a Shield token to a unit with Sentinel. P1 shields
#// its Sentinel unit (SOR_063).

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_242}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
