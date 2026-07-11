# SHD_003 Finn (front Action [Exhaust]) — "Defeat a friendly upgrade on a unit. If you do, give a Shield
# token to that unit." SOR_046 wears SOR_069; using Finn's Action defeats the upgrade and shields SOR_046.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
