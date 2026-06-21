# SEC_006 Colonel Yularen (leader) — Action [Exhaust]: Attack with a unit. Then, you may attack with
# another unit that costs less than it. P1 attacks with SOR_095 (cost 2, power 3) into the enemy base,
# then chains SOR_128 (cost 1 < 2, power 3) into the base too. 3 + 3 = 6 base damage.

## GIVEN
P1LeaderBase: SEC_006/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:EXHAUSTED
