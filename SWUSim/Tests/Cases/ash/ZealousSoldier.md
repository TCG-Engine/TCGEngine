# WhenPlayed_AdvantageSelf
#// ASH_251 Zealous Soldier (Ground, 2/3, cost 2) — When Played: give an Advantage token to this unit.
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_251}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_251
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
