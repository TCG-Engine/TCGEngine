# OnAttack_HealsAFriendly_ThenPingsAnEnemy
#// IC27_031 BB-8 — Frazzled Friend (0/4 Ground, Vigilance/Heroism, Resistance·Droid, unique)
#// "On Attack: Heal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit."
#// IC27_031#0 (a CUSTOM — a mandatory pick queued straight from an On Attack closure is skipped) offers every
#// friendly unit; IC27_031#1 heals 1 and measures it. "If you do" = damage was actually healed; then a mandatory
#// enemy-unit pick deals 1 with BB-8 as the source.
#// PREVIEW JUDGEMENT CALLS: (a) healing an undamaged unit heals nothing, so "If you do" fails and there is no
#// ping; (b) with NO damaged friendly unit the heal can heal nothing whatever is chosen, so no prompt is raised.
#//
#// COVERAGE: offer=Offer_HealPool_EveryFriendlyUnit + Offer_PingPool_EnemyUnitsOnly ·
#//           decline=N/A (no "you may"; both picks are mandatory) ·
#//           boundary=UndamagedChosen_NoPing (0 healed) paired with OnAttack_HealsAFriendly_ThenPingsAnEnemy (1) ·
#//           control=N/A (no owner-scoped zone, no "your") ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P,TeamSuns (text says "friendly" and "enemy") · TeamSuns=TeamSuns_PoolsFollowTheTeam
#//
#// BB-8 attacks P2's base. P1 heals its damaged Battlefield Marine (2 -> 1), then pings P2's Consular
#// Security Force.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0   # idx 0 — BB-8 0/4
WithP1GroundArena: SOR_095:1:2    # idx 1 — Battlefield Marine 3/3, 2 damage
WithP2GroundArena: SOR_046:1:0    # idx 0 — Consular Security Force 3/7
WithP2GroundArena: SEC_080:1:0    # idx 1 — Imperial Dark Trooper 3/3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# Offer_HealPool_EveryFriendlyUnit
#// The heal pool is every friendly unit (BB-8 included, undamaged ones included) and no enemy. Left pending.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# Offer_PingPool_EnemyUnitsOnly
#// After a real heal, the damage pool is every ENEMY unit (both arenas), none of P1's. Left pending.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# UndamagedChosen_NoPing
#// "If you do": P1 chooses an UNDAMAGED unit (BB-8) while the Marine is damaged — nothing is healed, so no
#// enemy is damaged and no second prompt appears.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# NoDamagedFriendly_NoPrompt
#// No friendly unit is damaged: the heal can heal nothing, so nothing is offered and no enemy is damaged.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# NoEnemyUnit_HealStillHappens
#// The heal resolves; the ping has no target and fizzles cleanly.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION

---

# PingKills_OneHpEnemy
#// The 1 damage is real: a damaged Imperial Dark Trooper on 2 damage (1 remaining) is defeated.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# AnsweredAcrossRequestBoundary
#// The healed amount decides the ping; a request boundary sits between the heal answer and the ping answer.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_031:1:0
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# TeamSuns_PoolsFollowTheTeam
#// Team Suns: teammate P3's damaged unit is in the HEAL pool; after healing it, the PING pool holds the
#// enemies P2 and P4 only — never the teammate.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_031:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:2
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:1
P1HASDECISION
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0
