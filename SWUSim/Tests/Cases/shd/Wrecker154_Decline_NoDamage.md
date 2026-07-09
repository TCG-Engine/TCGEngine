# SHD_154 Wrecker — declining the optional resource-defeat (AnswerDecision:-) does nothing: no resource is
# lost (still 7) and no damage is dealt.

## GIVEN
CommonSetup: rrw/rrw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_154
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:7
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
