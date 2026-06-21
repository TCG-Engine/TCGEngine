# SEC_038 Condemn — Sentinel loss is immediate at attack declaration; the -6/-0 is NOT (it only lands
#   if/after the defender discloses). P1's SOR_063 (2/4 Sentinel) bears 1 Condemn and attacks P2's base.
#   The granted On Attack queues P2's disclose, which pauses combat. Mid-attack (disclose still pending):
#     - the attacker has LOST Sentinel (lose-all-other-abilities is active from declaration), and
#     - its power is STILL 2 (the -6/-0 only applies once the disclose resolves, not yet).
#   P2 still has the pending disclose decision.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:2
P2HASDECISION
