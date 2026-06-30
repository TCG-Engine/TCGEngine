# SEC_165 Academy Disciplinarian (Unit, Aggression, cost 3) — When Played: you may deal 1 to a friendly
#   unit with 2 or less power and ready it. Exhausted SOR_237 (power 2) → 1 damage + readied.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:0:0
WithP1Hand: SEC_165

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:READY
P1NODECISION
