# SOR_245 Medal Ceremony — the "attacked this phase" filter. Two Rebel Troopers (SOR_046); only idx0
# attacks. Medal Ceremony's target list is just idx0 (the attacker) — idx1 is a Rebel but did NOT
# attack, so it's excluded and gets no token.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
