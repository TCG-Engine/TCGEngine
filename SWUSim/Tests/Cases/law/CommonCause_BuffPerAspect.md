# LAW_167 Common Cause (Command event, cost 2) — "Give a unit +1/+1 for this phase for each different
# aspect among units you control." P1 controls SOR_095 (Command,Heroism) + SOR_225 (Villainy) = 3
# distinct aspects {Command,Heroism,Villainy} -> chosen SOR_095 gets +3/+3 (3/3 -> 6/6).

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
