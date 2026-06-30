# SEC_107 Chancellor Valorum (Ground, 3/7, Command/Command) — When this unit completes an attack:
#   you may disclose CommandCommandCommand → put the top card of your deck into play as a resource.
# Valorum (idx0) attacks P2 base (3 power, survives). On attack-end: disclose SEC_080 + SEC_094 +
# SEC_096 (each has a Command icon → 3 Command) → ramp the top deck card into a ready resource.
# Resources 2 → 3, deck 2 → 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_107:1:0
WithP1Hand: SEC_080
WithP1Hand: SEC_094
WithP1Hand: SEC_096
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P2BASEDMG:3
P1RESCOUNT:3
P1DECKCOUNT:1
P1NODECISION
