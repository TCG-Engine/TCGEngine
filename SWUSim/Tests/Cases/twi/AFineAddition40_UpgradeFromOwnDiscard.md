# TWI_040 A Fine Addition — "from any player's discard pile": play an Upgrade out of your OWN discard.
# SOR_120 sits in P1's discard; after the kill, P1 plays it from discard onto the Marine.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4
