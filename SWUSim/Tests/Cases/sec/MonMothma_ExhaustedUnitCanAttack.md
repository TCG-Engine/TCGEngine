# SEC_103 Mon Mothma — "even if those units are exhausted." The only other friendly unit (SOR_046) is
#   EXHAUSTED, yet Mon Mothma lets it attack: it defeats P2's SOR_128. Proves exhausted units are offered
#   and can attack via this ability.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
