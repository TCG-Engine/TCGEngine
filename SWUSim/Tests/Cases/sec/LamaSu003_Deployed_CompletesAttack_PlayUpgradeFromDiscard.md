# SEC_003 Lama Su (deployed) — "When this unit completes an attack (and survives): You may play an
# upgrade from your discard pile on a friendly non-Vehicle unit. It costs 1 resource less."
# SEC_003 (3/7) attacks the enemy base (no counter, survives) → onAttackEnd → plays SOR_070 (Vigilance,
# +1/+1, cost 2 → 1) from discard onto the friendly non-Vehicle SOR_095 (3/3 → 4/4). bbk base covers
# Vigilance. 3 ready → 2 (proves the −1). No "deal 1" rider on the deploy side.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3;discardCardIds:SOR_070}
P1OnlyActions: true
WithP1GroundArena: SEC_003:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_070
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:1:DAMAGE:0
P1RESAVAILABLE:2
P1DISCARDCOUNT:0
