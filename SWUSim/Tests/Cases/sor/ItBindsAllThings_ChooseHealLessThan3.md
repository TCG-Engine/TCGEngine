# SOR_075 It Binds All Things — "Heal UP TO 3" — the player may choose to heal LESS than 3 even when
# more damage is available. SOR_046 has 3 damage, but P1 chooses to heal only 1 (NUMBERCHOOSE → 1):
# SOR_046 is left at DAMAGE:2, and "deal that much" deals only 1 to the enemy (LAW_124 → DAMAGE:1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1
