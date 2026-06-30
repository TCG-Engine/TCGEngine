# ASH_212 Peli Motto (Ground, 1/1, cost 1) — "Ignore the aspect penalties of the first non-unit card you
# play each phase." With Peli in play under a Vigilance base/leader, P1 plays ASH_136 (a Command event,
# cost 2) — normally +2 off-aspect = 4 — for just 2 resources (the penalty is waived), buffing SOR_095 to 6.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_136}
WithP1GroundArena: ASH_212:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1RESAVAILABLE:0
