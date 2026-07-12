# TWI_221 In Pursuit (Event, cost 0, Tactic) — "Exhaust a friendly unit. If you do, exhaust an enemy
# unit." Exhausts the friendly SOR_095 (auto, only friendly) then the enemy SOR_128 (auto, only enemy).

## GIVEN
CommonSetup: yyk/bbw/{myResources:1;handCardIds:TWI_221}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
