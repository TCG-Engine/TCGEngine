# SOR_155 Aggression — "Defeat up to 2 upgrades" now spans DIFFERENT units (two chained "may defeat 1"
# flows, each re-reading the board). SEC_080 holds SOR_120 and SOR_095 holds SOR_069; DefeatUpgrades
# removes one upgrade from EACH (impossible with the old host-scoped single flow). The second mode is
# Draw. Aggression,Aggression is fully off-aspect for SOR_009 → cost 8.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_237
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatUpgrades
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:3
