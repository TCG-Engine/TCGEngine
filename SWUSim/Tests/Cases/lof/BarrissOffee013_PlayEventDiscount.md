# LOF_013 Barriss Offee — Action [Exhaust, use the Force]: Play an event from your hand. It costs 1 less.
# P1 plays SOR_073 (Moment of Peace) which gives Plo Koon a Shield; the Force is spent.

## GIVEN
P1LeaderBase: LOF_013/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_073
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NOFORCE
