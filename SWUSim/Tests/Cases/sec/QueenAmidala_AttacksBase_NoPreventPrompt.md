# SEC_101 Queen Amidala — ATTACKING A BASE: a base deals no counter-damage, so no damage would be dealt
# to Amidala. Her "if damage would be dealt to this unit, you may defeat a trait-sharing friendly to
# prevent it" must NOT prompt (previously it wrongly offered the sacrifice for nothing). She hits the
# enemy base for 5, the trait-sharing friendly (SEC_118) survives, and there is no pending decision.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_118
P1NODECISION
