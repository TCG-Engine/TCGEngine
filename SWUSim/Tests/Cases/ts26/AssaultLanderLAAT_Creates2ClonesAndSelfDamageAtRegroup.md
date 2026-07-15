# TS26_023 Assault Lander LAAT (Unit 3/5, cost 4) — When Played: create 2 Clone Trooper tokens. When the
# regroup phase starts: deal 4 damage to this unit. After playing it and passing to regroup, the board has
# the LAAT (4 damage) + 2 Clone Trooper tokens.
## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_023}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>Pass
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TS26_023
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
