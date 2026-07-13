# TWI_034 General Grievous — the aspect-penalty waiver is host-specific (only when the Lightsaber is
# played ON Grievous). With a non-Grievous host (SEC_080), TWI_121 (Command Lightsaber) keeps its +2
# off-aspect penalty → costs 5, unaffordable on 3 resources: the play silently fails, the upgrade stays
# in hand and nothing is attached.
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_121}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:3
