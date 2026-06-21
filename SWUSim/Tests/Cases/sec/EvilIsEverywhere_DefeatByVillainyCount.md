# SEC_247 Evil is Everywhere (Event, Villainy, cost 3) — "Defeat a unit with cost <= the number of
#   Villainy aspect icons among friendly units." One friendly Villainy unit (SEC_193 = 1 Villainy) →
#   count 1 → defeat the cost-1 enemy SOR_128 (auto; SEC_193 cost 7 isn't a valid target).

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_193:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_247

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION
