# SEC_183 Topple the Summit (Event, Aggression, cost 5) — "Deal 3 to each damaged unit." (Plot auto.)
#   The two damaged units take 3 more (→ 5); the undamaged unit is untouched.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_183

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION
