# PlayedGiveAdvantage
#// ASH_167 Flarestar Attack Shuttle (Space, 2/1) — When Played/When Defeated: you may give an Advantage
#// token to a unit. On play, gives one to a friendly Marine.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_167}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
