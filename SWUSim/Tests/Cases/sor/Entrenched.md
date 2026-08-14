# CanStillAttackUnit
#// SOR_072 Entrenched — only BASE attacks are blocked; the host can still attack units. SOR_095 +
#// Entrenched (→ 6/6) attacks an enemy unit (SOR_046, 3/7) for DAMAGE:6.
#// COVERAGE: offer=AttachOffer_IncludesEnemyUnits (pending SELECTABLEEXACT — no printed attach
#//           restriction, so the pool is ANY unit per CR 2.e) · control=PlayedOnEnemyUnit_* (the
#//           restriction and the +3/+3 ride the HOST, an enemy-controlled unit) · reqboundary=
#//           PlayedOnEnemyUnit_HostStillAttacksUnits_BuffApplies (attach answered, then the opponent
#//           attacks on a later request) · boundary pair=CantAttackBase (base attack refused) +
#//           CanStillAttackUnit (unit attack allowed) · decline=N/A (no "you may" anywhere on the card)

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_072
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# CantAttackBase
#// SOR_072 Entrenched (Vigilance upgrade, cost 2, +3/+3, Condition) — "Attached unit can't attack
#// bases." SOR_095 + Entrenched (→ 6/6) tries to attack the enemy base: the attack is blocked, so the
#// base takes no damage.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_072

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0

---

# AttachOffer_IncludesEnemyUnits
#// Intended: Entrenched has no printed attach restriction, so per CR 2.e it can attach to ANY
#// unit — the offer holds both the friendly Marine and the enemy Security Force. The decision is
#// left pending so the offer itself is what gets asserted.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myhandCardIds:SOR_072}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# PlayedOnEnemyUnit_HostCantAttackMyBase
#// Intended: played on an OPPONENT's unit, the restriction binds that unit — it can no longer
#// attack MY base. The enemy TIE/ln is the only unit in play, so the attach auto-resolves onto it;
#// the opponent's base attack is then refused (base takes 0, the TIE stays ready).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myhandCardIds:SOR_072}
WithP2SpaceArena: SOR_225:1:0    # TIE/ln Fighter (2/1) — sole unit, auto-attach host

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:READY
P1BASEDMG:0

---

# PlayedOnEnemyUnit_HostStillAttacksUnits_BuffApplies
#// Intended: the enemy-hosted Entrenched still grants its +3/+3 — the opponent's TIE/ln (2/1 →
#// 5/4) may attack my units, just not my base. P1 picks the enemy TIE as the host (two candidates
#// keep the attach interactive), then P2 attacks my Bright Hope (2/6): it takes 5, the TIE takes
#// 2 back.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myhandCardIds:SOR_072}
WithP1SpaceArena: SOR_099:1:0    # Bright Hope (2/6) — second attach candidate, then the defender
WithP2SpaceArena: SOR_225:1:0    # TIE/ln Fighter (2/1) — the chosen enemy host

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:5
P2SPACEARENAUNIT:0:DAMAGE:2
P1BASEDMG:0
