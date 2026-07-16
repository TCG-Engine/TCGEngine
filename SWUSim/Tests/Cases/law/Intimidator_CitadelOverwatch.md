# ReturnResourcesForCredits
#// LAW_140 Intimidator (Command,Villainy, cost 11) — When Played: return any number of friendly
#// resources to their owners' hands. For each resource returned, create a Credit token. Return 2 of the
#// (exhausted-after-paying) resources -> 2 cards to hand, 2 Credits, 9 resources left.

## GIVEN
CommonSetup: grk/bgw/{myResources:11}
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:9
P1CREDITCOUNT:2
P1HANDCOUNT:2
