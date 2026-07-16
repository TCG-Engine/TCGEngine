# UseForce_ReadySelf
#// LOF_195 Vernestra Rwoh (3/4) — When Played: may use the Force → ready this unit. P1 plays her (enters
#// exhausted) and uses the Force to ready her.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:LOF_195}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:READY
