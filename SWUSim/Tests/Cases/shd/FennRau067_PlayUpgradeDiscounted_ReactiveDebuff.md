# SHD_067 Fenn Rau (6-cost ground) — "When Played: You may play an upgrade from your hand. It costs 2 less."
# + "When you play an upgrade on this unit: Give an enemy unit -2/-2 for this phase." Playing Fenn Rau, P1
# plays SOR_120 (cost 2 → 0) which auto-attaches to Fenn Rau (the only host); that upgrade-play triggers the
# reactive, giving the enemy SOR_046 -2/-2 (3/7 → 1/5). Total cost 6 → 0 resources left.

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
