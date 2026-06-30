# ASH_199 There Is No Conflict (Upgrade, +2/+2, cost 2) — When Played: return any number of OTHER upgrades
# on the attached unit to their owners' hands. Played onto SOR_095 (which already carries SOR_120), it
# returns SOR_120 to hand. SOR_095 ends at 3 + ASH_199(+2) = 5 power, with SOR_120 back in hand.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_199}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:1
