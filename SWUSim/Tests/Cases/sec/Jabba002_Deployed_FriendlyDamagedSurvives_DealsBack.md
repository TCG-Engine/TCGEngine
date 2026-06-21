# SEC_002 Jabba the Hutt (deployed) — "When another friendly unit is dealt damage and survives: You may
# have that unit deal that much damage to an enemy unit. Once each round."
# P1's SEC_080 (3/3) attacks the enemy SOR_063 (2/4 Sentinel): deals 3 (SOR_063 survives at 4 HP),
# takes 2 counter-damage and survives. SEC_002 (deployed) reacts → SEC_080 deals that much (2) to an
# enemy unit. Only enemy = SOR_063 → 3 + 2 = 5 damage on 4 HP → defeated.

## GIVEN
P1LeaderBase: SEC_002:1:1:1/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_002:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
