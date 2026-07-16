# ReturnSameName
#// TWI_199 Clear the Field (Event, Cunning/Heroism) — "Choose a non-leader unit that costs 3 or less. Return
#// it and each enemy non-leader unit with the same name to their owners' hands." Both enemy SOR_095 bounce.
## GIVEN
CommonSetup: yyw/bbw/{myResources:2;handCardIds:TWI_199}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:2
