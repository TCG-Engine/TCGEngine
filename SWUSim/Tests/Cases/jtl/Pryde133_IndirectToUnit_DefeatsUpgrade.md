# JTL_133 Allegiant General Pryde — On Attack: if you have the initiative, deal 2 indirect to a player;
# AND "when indirect damage is dealt to a unit, you may defeat a non-unique upgrade on it." Pryde attacks
# P2's base; with initiative the On Attack deals 2 indirect to P2, who puts both on SOR_046 (carrying a
# non-unique upgrade SOR_120). Because P1 controls Pryde, the indirect-to-a-unit reaction lets P1 defeat
# SOR_120 on it.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
