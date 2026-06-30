# ASH_128 Bothan-5 (Space, 4/5, cost 5) — When another friendly non-Vehicle unit is defeated: you may have
# this unit capture that unit from your discard pile (once each round). SOR_095 (non-Vehicle) dies attacking
# SOR_046; P1 captures it from the discard onto Bothan-5, so it leaves the discard (and isn't in the arena).
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
