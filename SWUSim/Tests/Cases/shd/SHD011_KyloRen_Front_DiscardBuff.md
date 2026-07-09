# SHD_011 Kylo Ren (front Action [Exhaust, discard a card]) — "Give a unit +2/+0 for this phase." P1
# discards SOR_046 from hand (cost) and buffs SOR_095 (3/3 → 5/3).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_011}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:1
