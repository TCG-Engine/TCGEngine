# WhenPlayed_SelfDmgAndAdvantage
#// ASH_158 Han Solo (Ground, 3/7, Saboteur) — When Played: deal 3 to this unit; give 3 Advantage tokens
#// to a unit. Plays, takes 3 self-damage (survives, 7 HP), gives itself 3 Advantage tokens.
## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:ASH_158}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_158
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
