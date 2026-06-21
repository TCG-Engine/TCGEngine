# LOF_015 Cal Kestis — Action [Exhaust, use the Force]: An opponent chooses a ready unit they control;
# exhaust that unit. P1 uses the Force; P2 chooses SOR_046 (from its two ready units) to be exhausted.

## GIVEN
P1LeaderBase: LOF_015/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NOFORCE
