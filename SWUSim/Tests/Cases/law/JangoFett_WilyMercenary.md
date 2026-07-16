# OnAttackExhaustIfUpgraded
#// LAW_087 Jango Fett (6/5, Shielded) — On Attack: if this unit is upgraded, exhaust an enemy unit.
#// Jango bears SOR_120 (upgraded); attacks the base; exhaust the enemy SEC_080.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_087:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
