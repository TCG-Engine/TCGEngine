# SEC_085 Vice Admiral Rampart (Ground, 3/6, Command/Villainy) — On Attack: you may disclose
#   CommandCommandVillainy → give an Experience token to each of up to 2 OTHER units.
# Rampart (idx0) attacks P2 base (3 power). On Attack: disclose two SEC_080 (Command,Villainy each →
# cover CommandCommandVillainy) → give Experience to the two other friendly fillers (idx1, idx2),
# each 3/3 → 4/4 with one upgrade (the token).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_085:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SEC_080
WithP1Hand: SEC_080

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:myGroundArena-1&myGroundArena-2

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:2:POWER:4
P1NODECISION
