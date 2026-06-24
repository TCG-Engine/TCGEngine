# ASH_140 Stronger Together (Event) — create 2 Mandalorian tokens.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:ASH_140}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
