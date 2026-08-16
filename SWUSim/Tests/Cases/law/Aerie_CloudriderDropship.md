# OnAttackDealGroundAndBase
#// LAW_184 Aerie (3/7, space) — On Attack: deal 2 damage to an enemy ground unit and 2 damage to a base.
#// Attacks the base: base takes 3 (combat) + 2 (ability) = 5; enemy SOR_046 takes 2.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:5

---

# OnAttackNoGroundUnitJustBase
#// LAW_184 Aerie — On Attack with NO enemy ground unit in play, the "deal 2 to an enemy ground unit" part
#// has no target; you still deal 2 to a base. Attacks the base: 3 (combat) + 2 (ability) = 5.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5

---

# OfferPool_EnemyGroundUnitsOnly
#// LAW_184 Aerie — offer assertion for the "deal 2 damage to an enemy ground unit" clause. The board is
#// built to DISCRIMINATE: P1 also fields a friendly GROUND unit (SOR_095) and P2 also fields an enemy
#// SPACE unit (SEC_213), so the pool must reject both a same-arena friendly and a same-controller
#// wrong-arena unit. Two enemy ground units keep the MZMAYCHOOSE genuinely pending (a single legal
#// target would still prompt here, but two also rules out a first-match shortcut). The pick is left
#// UNANSWERED so the pending pool can be read — EXPECT evaluates at end state only, and answering the
#// decision would consume it. Pool must be exactly the two enemy ground units; Aerie itself (space) out.
#// COVERAGE: offer=OfferPool_EnemyGroundUnitsOnly (pending SELECTABLEEXACT with a friendly-ground and an
#//           enemy-space violator on the board) + OfferPool_BaseClauseOffersEitherBase for the SECOND
#//           clause. That clause used to raise no pool at all — the 2 was hardcoded to the enemy base, so
#//           a controller who wanted to hit their OWN base could not, unlike the identically-worded
#//           LAW_057 Benthic. Found and FIXED this pass (2026-08-16) ·
#//           reqboundary=N/A (nothing is written before the pick and re-read after it: the base damage is
#//           applied inline, and the unit pick runs the shared DEAL_UNIT_DAMAGE continuation) ·
#//           control=N/A (one-shot On-Attack damage; no persistent per-unit marker to survive a control
#//           change) · boundary pair=OnAttackDealGroundAndBase (a legal enemy ground unit exists) vs
#//           OnAttackNoGroundUnitJustBase (empty pool → the clause silently no-ops) ·
#//           decline=NOT COVERED (the ping is a "you may"; no decline section exists yet)

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# OfferPool_BaseClauseOffersEitherBase
#// LAW_184 Aerie, SECOND clause — "and 2 damage to a base" names no controller, so BOTH bases are legal
#// and the pick must be pending. Aerie attacks the base with no enemy ground unit in play, so the first
#// clause finds no target and the base choose is the only decision left to read.
#// Regression guard: this clause used to deal the 2 straight to the enemy base with no prompt at all.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# BaseClause_CanChooseYourOwnBase
#// LAW_184 Aerie — the half the enemy-base pick cannot prove: choosing YOUR OWN base actually routes the
#// 2 there. Combat (power 3) still hits the enemy base, so the two totals separate cleanly:
#// own base 2 (ability only), enemy base 3 (combat only).

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:3
