# OwnBaseAt15_GainsSentinel
#// HMW_074 Yord Fandar - Devoutly Disciplined (Vigilance/Heroism, Force+Jedi, cost 2, 2/4 Ground,
#// unique) - "While a base has 15 or more damage on it, this unit gains Sentinel."
#// COVERAGE: offer=SentinelForcesEnemyAttackOntoYord + NoSentinel_EnemyAttackHitsTheBaseAsRequested
#//           (the attack-target POOL, proven behaviourally: with Sentinel up the pool narrows to
#//           {Yord} so the attack auto-resolves and an explicit ':BASE' request is overridden;
#//           SELECTABLEEXACT is unusable here because declareAttack answers the picker inline) ·
#//           negative=NoBaseAt15_NoSentinel + EnemyBaseStopsAt14_NoSentinel +
#//           OnlyYordGainsSentinel_NotOtherFriendlyUnits ·
#//           boundary pair=EnemyBaseReachesExactly15 (15 -> yes) vs EnemyBaseStopsAt14 (14 -> no),
#//           both reached DYNAMICALLY by the same attack so the threshold, not the fixture, is pinned ·
#//           control=StolenYord_StillReadsEitherBase · reqboundary=RequestBoundary_ConditionSurvives ·
#//           decline=N/A (continuous passive, no "you may", no cost)
#// ⚠ "a base" carries NO controller qualifier, so it means EITHER base - same wording as SOR_148
#//   Guerilla Attack Pod ("If a base has 15 or more damage on it"), and deliberately NOT TWI_142
#//   Anakin's Interceptor ("While YOUR base has 15 or more damage on it"). Both readings are live in
#//   this engine, so the enemy-base sections below are the load-bearing half of this card.
#// Here: the controller's OWN base at exactly 15.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1BASEDMG:15

---

# NoBaseAt15_NoSentinel
#// HMW_074 - the negative that makes the gate load-bearing: BOTH bases sit at 14, one short. A card
#// that granted Sentinel unconditionally, or read the wrong threshold, passes every positive section
#// and fails only here.
#// (Green before implementation - an absence guard. It stays meaningful as the boundary partner.)

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:14;theirBaseDamage:14}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# EnemyBaseReachesExactly15_GainsSentinel
#// HMW_074 - the unqualified-"a base" half AND the recompute half in one section. P1's OWN base is
#// clean; the OPPONENT's base starts at 12 and Battlefield Marine (3 power) pushes it to exactly 15.
#// Yord must gain Sentinel from an enemy base he has nothing to do with, and must gain it MID-GAME -
#// a controller-scoped read denies it, and a value stamped at seat time never appears.

## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:12}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:15
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# EnemyBaseStopsAt14_NoSentinel
#// HMW_074 - the boundary partner for the section above, identical except the enemy base starts at 11
#// so the same 3-power attack lands on 14. Without this pair the positive passes for ANY threshold
#// value, and it also proves the ATTACK itself is not what grants Sentinel.
#// (Green before implementation - an absence guard.)

## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:11}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:14
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SentinelForcesEnemyAttackOntoYord
#// HMW_074 - the granted keyword actually WORKING, not merely being readable. P1's base is at 15, so
#// Yord has Sentinel; P2's Dark Trooper asks to attack the BASE. Sentinel narrows the legal-target
#// pool to {Yord} alone, so BeginSWUAttack auto-resolves inline and the explicit ':BASE' request is
#// never honoured (declareAttack only injects a target when a picker is actually pending). Yord takes
#// 3 (survives on 4 HP) and counters for 2; the base and the bystander are untouched.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
WithActivePlayer: 2
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:15
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoSentinel_EnemyAttackHitsTheBaseAsRequested
#// HMW_074 - the mirror of the section above, identical board except P1's base is undamaged. With no
#// base at 15 the pool is {Yord, Battlefield Marine, base}, the picker really appears, and the ':BASE'
#// request lands: 3 damage to the base and nobody in the arena is touched. This is what proves the
#// redirect in the previous section came from Sentinel and not from the harness.
#// (Green before implementation - an absence guard.)

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:0}
WithActivePlayer: 2
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# OnlyYordGainsSentinel_NotOtherFriendlyUnits
#// HMW_074 - scope exclusion. "this unit gains Sentinel" is self-only; the condition is a board fact
#// that is equally true for every unit in play, so an implementation that hangs the grant off the
#// CONDITION rather than off the RECIPIENT hands Sentinel to the whole team. Battlefield Marine, on
#// the same board with the same base at 15, must not have it.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# StolenYord_StillReadsEitherBase
#// HMW_074 - the control cell, and it discriminates independently of the enemy-base sections. P1 OWNS
#// Yord, P2 CONTROLS him, and the only damaged base (15) is P1's - i.e. the CONTROLLER's base is
#// clean. A $ctrl-scoped scan (the natural way to write this card, and what TWI_142 legitimately does)
#// answers NO here; the correct "either base" reading answers YES.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
P1OnlyActions: true
WithP2GroundArenaControlled: HMW_074:1

## WHEN
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_074
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1BASEDMG:15

---

# RequestBoundary_ConditionSurvives
#// HMW_074 - the request-boundary cell. The card writes no state across a decision, but production
#// starts a FRESH PROCESS between the two player actions below, so the boundary belongs there: any
#// implementation that memoised the base-damage answer into a transient global would answer stale.
#// Same board and same assertions as SentinelForcesEnemyAttackOntoYord, with the boundary inserted
#// before P2 acts.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
WithActivePlayer: 2
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>SimulateRequestBoundary
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:15
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# BaseHealedBelow15_LosesSentinel
#// HMW_074 - "While ..." is a continuous read that must RECOMPUTE, not a one-time stamp. P1's base
#// starts at 15 (Yord has Sentinel), then Gunga City Guard HMW_084 attacks and its Restore 1 heals
#// P1's base back down to 14 - and the keyword must go away in the same action. A grant applied once
#// when the threshold was first crossed passes every other section in this file.
#// (Green before implementation - an absence guard; it is the only downward-recompute proof.)

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: HMW_084:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:14
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:HMW_074
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
