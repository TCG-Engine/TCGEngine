# SHD_213 DJ — "When this unit leaves play, that resource's owner takes control of it." After the
# steal, P2's AT-AT Suppressor (SOR_039, 8/8) defeats DJ (3/5): the lazy leave-play sweep returns
# the stolen resource to P2. P1 back to 8 resources, P2 back to 2.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:8
P2RESCOUNT:2
