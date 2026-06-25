# LOF_010 Third Sister — Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase. Plo
# Koon enters with Hidden.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_050
WithP1Resources: 10

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
