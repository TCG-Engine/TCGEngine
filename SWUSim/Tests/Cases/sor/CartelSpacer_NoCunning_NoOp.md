# SOR_178 Cartel Spacer — the exhaust is conditional on controlling ANOTHER Cunning unit.
# Here P1's only other unit is Battlefield Marine (Command, not Cunning), so the condition
# fails and the enemy unit stays ready. (Cartel Spacer is itself Cunning, but "another"
# excludes it.) Absence guard.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1GroundArena: SEC_080:1:0    # friendly Command unit (NOT Cunning)
WithP2GroundArena: SEC_080:1:0    # enemy unit — must stay ready

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
