# TWI_040 A Fine Addition — the condition ("If an enemy unit was defeated this phase") is NOT met, so the
# event fizzles: no upgrade is played, the upgrade stays in hand, the friendly unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
