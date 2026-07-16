# ActionSetPrintedHpTo1
#// LAW_126 Adventurer Sniper Rifle (Upgrade) — grants "Action [Exhaust]: Choose an undamaged non-leader
#// ground unit. Its printed HP is considered to be 1 for this phase." SEC_080 wears the rifle and uses
#// the action targeting the enemy SOR_046 (3/7, undamaged); its HP becomes 1. The host SEC_080 exhausts.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED
