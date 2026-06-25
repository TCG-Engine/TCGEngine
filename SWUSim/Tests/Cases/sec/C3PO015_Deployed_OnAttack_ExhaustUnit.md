# SEC_015 C-3PO (deployed) — On Attack: If you control another exhausted unit, you may exhaust a unit.
# Deployed SEC_015 (1/6) attacks the enemy base while controlling another exhausted unit (SOR_095) → may
# exhaust a unit → exhausts the ready enemy SOR_128.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
