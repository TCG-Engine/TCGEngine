# ASH_218 Ferry Droid (Ground, 1/5, cost 3) — When Played: give 4 Advantage tokens to this unit.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_218}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_218
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:4
