# TWI_040 A Fine Addition — "ignoring its aspect penalty": SOR_120 (Command, base cost 2) is fully
# off-aspect under an Aggression/Villainy board. With only 2 ready resources it is affordable ONLY because
# the aspect penalty is waived (unignored it would cost 4 and could not be offered → the event would
# fizzle and POWER would stay 3). It attaches, spending exactly its base cost (2 → 0 resources).
## GIVEN
CommonSetup: brk/bbw/{myResources:2;handCardIds:TWI_040}
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
P1RESAVAILABLE:0
