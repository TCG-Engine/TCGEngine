# LAW_158 Khetanna (2/4) — When Played/On Attack: the next Underworld unit you play this phase costs 1
# resource less. Khetanna attacks the base (arming the discount), then LAW_134 (Underworld, cost 2)
# plays for 1: with 1 ready resource it leaves hand and ends at 0 ready (proving the discount).

## GIVEN
CommonSetup: grk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_158:1:0
WithP1Hand: LAW_134

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0
