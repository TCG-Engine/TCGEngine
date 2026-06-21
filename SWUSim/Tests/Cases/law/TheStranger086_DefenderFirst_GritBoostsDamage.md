# LAW_086 The Stranger (1/7, Grit) — "While attacking, you may have the defending unit deal combat
# damage before this unit." This combos with Grit: The Stranger attacks Battlefield Marine (3/3) and
# chooses defender-first. The Marine deals 3 to The Stranger first (7 HP → survives, 3 damage); Grit then
# raises The Stranger's power from 1 to 4 (+1 per damage), so it deals 4 to the Marine (3 HP) → defeated.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_086
P1GROUNDARENAUNIT:0:DAMAGE:3
