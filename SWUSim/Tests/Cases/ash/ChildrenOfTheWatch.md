# WhenPlayed_TwoTokens
#// ASH_111 Children of the Watch (Ground, 3/3) — When Played: create 2 Mandalorian tokens.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:ASH_111}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_111
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
