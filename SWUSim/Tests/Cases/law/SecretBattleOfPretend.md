# ExhaustPerAspect
#// LAW_226 Secret Battle of Pretend (Cunning,Heroism event, cost 2) — "Exhaust a friendly unit. If you
#// do, for each different aspect it has, exhaust an enemy unit in the same arena." SOR_046 (Vigilance,
#// Heroism = 2 aspects) -> exhaust 2 enemy ground units.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
