# ASH_124 Protectorate Fighter (Space, 2/1) — When Played: if you control a unique unit, create a
# Mandalorian token (a ground Token Unit). P1 controls Obi-Wan (SOR_049, unique) → token created.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:ASH_124}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:ASH_124
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
