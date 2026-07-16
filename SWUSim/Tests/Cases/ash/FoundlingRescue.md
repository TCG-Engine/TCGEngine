# Event_DefeatLowHpAndToken
#// ASH_092 Foundling Rescue (Event) — you may defeat a unit with 2 or less remaining HP; create a
#// Mandalorian token. P1 defeats the 3/1 Stormtrooper and gets a Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
