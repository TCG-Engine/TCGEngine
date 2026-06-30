# SEC_177 It's Not Over Yet — a unit that attacked this phase is NOT eligible to ready. SOR_095
#   attacks the base (exhausted + marked attacked-this-phase); then SEC_177 offers no ready target, so
#   the unit stays exhausted and only the Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_177

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1NODECISION
