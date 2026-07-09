# SHD_046 Rey (5-cost 4/7 ground) — "On Attack: You may heal 2 damage from a unit. If it's a non-Heroism
# unit, give a Shield token to it." Rey heals the enemy SEC_080 (Villainy = non-Heroism, 2 damage → 0) and,
# because it's non-Heroism, gives it a Shield.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_046:1:0
WithP2GroundArena: SEC_080:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
