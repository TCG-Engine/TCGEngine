# Initiative_Deal1ToThree
#// LOF_167 Saesee Tiin — When Played: if you have the initiative, deal 1 damage to each of up to 3 units.
#// P1 holds the initiative and deals 1 to each of three enemy 3/7 units.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_167}
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1
