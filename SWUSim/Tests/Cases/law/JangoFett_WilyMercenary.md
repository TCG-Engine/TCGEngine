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

---

# ShieldedGivesHimAnUpgrade_WhichIsWhatArmsTheOnAttack
#// LAW_087 Jango Fett — his two printed clauses interlock: Shielded gives him a Shield when he is PLAYED,
#// and a Shield is an upgrade, so the "if this unit is upgraded" gate on his On Attack is satisfied by his
#// own keyword with no help from anything else. Played from hand he arrives carrying exactly one upgrade.
#// Every other section seeds him with an Academy Training instead, so none of them shows where the upgrade
#// normally comes from.

## GIVEN
CommonSetup: byk/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_087

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_087
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NotUpgraded_NoExhaustAtAll
#// LAW_087 Jango Fett — "If this unit IS UPGRADED, exhaust an enemy unit" is a gate, and this is the only
#// section that fails it. Seeded straight into the arena he never gets his Shield, so attacking with no
#// upgrade on him raises no decision and the ready enemy SEC_080 stays ready. Boundary partner of
#// OnAttackExhaustIfUpgraded on the same board with one upgrade added.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_087:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:6
