# TWI_089 Consolidation of Power (Event, cost 6, Command/Villainy) — "Choose any number of friendly
# units. You may play a unit from your hand if its cost is <= the combined power of the chosen units for
# free. Then, defeat the chosen units." Choose SOR_095 (3) + SEC_080 (3) = combined 6; play SOR_046
# (cost 4 <= 6) for free; then the two chosen units are defeated. Board ends with just SOR_046.
## GIVEN
CommonSetup: ggk/bbw/{myResources:6;handCardIds:TWI_089,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1HANDCOUNT:0
