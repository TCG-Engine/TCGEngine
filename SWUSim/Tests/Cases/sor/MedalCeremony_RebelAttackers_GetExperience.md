# SOR_245 Medal Ceremony (Event, cost 0, Heroism) — "Give an Experience token to each of up to 3
# Rebel units that attacked this phase." Two Rebel Troopers (SOR_046, 3/7) and one non-Rebel Imperial
# Trooper (SOR_128) all attack the base this phase. Medal Ceremony's target list is ONLY the two
# Rebels that attacked — the Imperial (idx 2) attacked but is not a Rebel, so it's excluded. Choosing
# both Rebels gives each an Experience token (+1/+1): idx0/idx1 → UPGRADECOUNT 1 and 4/8; idx2 → none.
# Base damage (9 = 3+3+3) is dealt by the attacks BEFORE the tokens, so it reflects un-buffed power.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:2:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2BASEDMG:9
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
