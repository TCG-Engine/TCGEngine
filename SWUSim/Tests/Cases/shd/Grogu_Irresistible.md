# Action_ExhaustEnemy
#// SHD_196 Grogu (2-cost ground) — "Action [exhaust]: Exhaust an enemy unit." Using the action exhausts
#// Grogu (the cost) and the enemy SOR_046.
#// COVERAGE: offer=Action_Offer_EnemyUnitsOnly (pending P1SELECTABLEEXACT — friendly units excluded) ·
#//           decline=N/A (not a "you may" — the exhaust is mandatory once the cost is paid) ·
#//           control=Action_Offer_EnemyUnitsOnly (seat-scoped: the SAME SOR_046 is offered on P2's side
#//           and excluded on P1's) · boundary=Action_ExhaustEnemy (a target exists) vs
#//           Action_NoEnemyUnits_StillPaysTheExhaustCost (none exists — cost still paid) ·
#//           reqboundary=N/A (the target pick and the exhaust resolve in one step, no state read across it)

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_196:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_196
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Action_Offer_EnemyUnitsOnly
#// SHD_196 — the pool is enemy units only: P1's own SOR_046 (and Grogu himself) are never legal targets,
#// both of P2's ready units are. Decision left PENDING so the offer itself is the assertion.
#// Note: an already-EXHAUSTED enemy unit is filtered out of the pool here (exhausting it is a strict
#// no-op), which is why both enemies are seeded ready — one ready + one exhausted auto-resolves.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: [SHD_196:1:0 SOR_046:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Action_NoEnemyUnits_StillPaysTheExhaustCost
#// SHD_196 — the ability is an Action whose COST is exhausting Grogu; with no enemy unit anywhere the
#// effect has nothing to exhaust, but the action is still legal and the cost is still paid. Grogu ends
#// exhausted with no decision left pending.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_196:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_196
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
