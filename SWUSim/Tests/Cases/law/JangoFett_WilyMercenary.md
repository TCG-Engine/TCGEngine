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

---

# OfferPool_EnemyUnitsInBothArenas
#// LAW_087 Jango Fett — offer assertion for "On Attack: If this unit is upgraded, exhaust an ENEMY UNIT".
#// The restriction is controller-only: "enemy unit", with NO arena word, so a ground attacker may reach
#// into space. Discriminating board — a second friendly GROUND unit (SOR_095) and a friendly SPACE unit
#// (SOR_178) must both be OUT, while the enemy GROUND unit and the enemy SPACE unit must both be IN.
#// Jango himself is out (friendly). Two legal targets keep the MZMAYCHOOSE pending; the pick is left
#// UNANSWERED so the pool can be read.
#// COVERAGE: offer=OfferPool_EnemyUnitsInBothArenas (pending SELECTABLEEXACT; friendly units on both
#//           arenas are the "out", enemy ground AND enemy space are the "in" — an arena-narrowed pool
#//           fails here) · reqboundary=NOT COVERED (the pick runs the shared EXHAUST_UNIT continuation;
#//           no per-card payload is written before the decision) · control=NOT COVERED (the "if this
#//           unit is upgraded" gate reads the attacker's own subcards, not a controller-scoped marker) ·
#//           boundary pair=OnAttackExhaustIfUpgraded (upgraded → fires) vs NOT COVERED for the
#//           un-upgraded negative · decline=NOT COVERED (MZMAYCHOOSE; no decline section yet)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_087:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1SPACEARENAUNIT:0:CARDID:SOR_178
