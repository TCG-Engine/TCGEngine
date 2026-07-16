# UseForce_ShieldUnits
#// LOF_072 Priestesses of the Force (6/8) — When Played: may use the Force → give a Shield token to each
#// of up to 5 units. P1 plays it with the Force and shields two friendly units.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;handCardIds:LOF_072}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
