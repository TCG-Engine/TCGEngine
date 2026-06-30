# LOF_014 Grand Inquisitor — Action [Exhaust, use the Force]: Attack with a friendly unit. The defender
# gets -2/-0 for this attack. Plo Koon (6) attacks SOR_046 (3/7): SOR_046 takes 6, its counter is reduced
# from 3 to 1, so Plo Koon takes only 1.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NOFORCE
