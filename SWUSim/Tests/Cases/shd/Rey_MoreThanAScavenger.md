# Rey_Deployed_Restore3_OnAttackExp
#// SHD_004 Rey (deployed) — Restore 3 (heal 3 from your base when she attacks) + On Attack: You may give
#// an Experience token to a unit with 2 or less power. Deployed (6 resources), Rey attacks the base:
#// Restore heals P1's base from 5 → 2, and her On Attack gives SHD_095 (power 2) an Experience token.
#// COVERAGE: offer=Deployed_OnAttack_Offer_BothArenasBothPlayersIncludingHerself (pending
#//           P1SELECTABLEEXACT — power-3 unit excluded, enemy unit included) ·
#//           decline=N/A (the front Action's grant is mandatory once paid; the deployed "you may" is a
#//           single-choice offer whose decline adds no board state that the other sections do not cover) ·
#//           control=Deployed_OnAttack_Offer_BothArenasBothPlayersIncludingHerself (the pool is NOT
#//           seat-scoped — P2's SOR_225 is a legal target of P1's ability) ·
#//           boundary=power 2 in vs power 3 out, both asserted in that same offer section; and
#//           Rey_Front_ExpToLowPower (undeployed, costs 1 resource + exhaust) vs
#//           Rey_Deployed_Restore3_OnAttackExp / Deployed_OnAttack_SelfExperience (deployed, free On Attack) ·
#//           reqboundary=Deployed_OnAttack_SelfExperience (the Experience is applied and re-read before
#//           combat damage is computed — base takes 3, not 2)

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004;myResources:6;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:3

---

# Rey_Front_ExpToLowPower
#// SHD_004 Rey (front Action [1 resource, Exhaust]) — "Give an Experience token to a unit with 2 or less
#// power." SHD_095 (power 2) is the lone eligible target → gets an Experience token (2/3 → 3/4). Rey
#// exhausts and 1 resource is spent.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Deployed_OnAttack_Offer_BothArenasBothPlayersIncludingHerself
#// SHD_004 (deployed) On Attack — "a unit with 2 or less power" is unqualified: the pool spans BOTH
#// arenas and BOTH players, and Rey herself (deployed 2/6) qualifies. P1 fields SHD_095 (power 2, in) and
#// SOR_046 (power 3, OUT); P2 fields SOR_225 (power 2, enemy, in). Rey deploys at ground index 2 and
#// attacks the base; the decision is left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SHD_095:1:0 SOR_046:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:2:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirSpaceArena-0

---

# Deployed_OnAttack_SelfExperience
#// SHD_004 (deployed) may hand the Experience token to HERSELF, and it lands before combat damage is
#// dealt: Rey (2/6) attacks the base, takes the Experience (→ 3/7) and P2's base takes 3, not 2.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
