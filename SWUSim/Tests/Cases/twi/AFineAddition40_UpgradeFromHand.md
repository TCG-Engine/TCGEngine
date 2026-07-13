# TWI_040 A Fine Addition — core: after defeating an enemy this phase (P1's Marine kills the 3/1 Trooper),
# play a regular Upgrade from hand. SOR_120 Academy Training (+2/+2, cost 2, Command) attaches to the only
# friendly unit (auto-resolved host). Command is off-aspect under an Aggression/Villainy board, but the
# aspect penalty is IGNORED, so it costs 2 (6→4 resources), not 4.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:4
