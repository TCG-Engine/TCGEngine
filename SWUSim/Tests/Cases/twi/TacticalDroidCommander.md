# ExhaustOnSeparatistPlay
#// TWI_184 Tactical Droid Commander (Unit 4/4, Ground, cost 5) — "Exploit 2. When you play another
#// Separatist unit: You may exhaust a unit that costs the same as or less than the played unit." P1
#// controls TWI_184 and plays JTL_069 (Separatist, cost 5); the reaction may-exhausts SEC_080 (cost 3 ≤ 5).

## GIVEN
CommonSetup: byk/grw/{myResources:5;handCardIds:JTL_069}
P1OnlyActions: true
WithP1GroundArena: TWI_184:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:CARDID:JTL_069
