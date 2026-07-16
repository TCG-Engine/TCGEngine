# PlayNonVehicleFromDiscardWithExp
#// TS26_057 Mechanize (Event, cost 2, Command) — Play a non-Vehicle unit from your discard (paying its
#// cost) and give it an Experience token. SEC_080 (3/3) is played from discard and gains 1 Experience →
#// 4/4 in play; only the Mechanize event remains in the discard.
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_057;discardCardIds:SEC_080}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1DISCARDCOUNT:1
