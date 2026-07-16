# Deal2ToEachGround
#// TWI_173 Blood Sport (Event, cost 3, Aggression, Fringe) — "Deal 2 damage to each ground unit." Both
#// players' ground units take 2 (SOR_046 3/7 survives at 2 damage); the space unit (SOR_237) is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_173}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:0
