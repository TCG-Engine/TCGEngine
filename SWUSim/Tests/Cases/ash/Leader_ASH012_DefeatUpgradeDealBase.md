# ASH_012 Vane — Leader Action [Exhaust, defeat a friendly upgrade]: deal 2 damage to a base. P1 pays the
# cost by defeating SOR_120 off SOR_095 (which reverts to 3 power), then deals 2 to P2's base.
## GIVEN
P1LeaderBase: ASH_012/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
