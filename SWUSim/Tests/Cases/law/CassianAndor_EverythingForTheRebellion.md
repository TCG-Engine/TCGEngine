# FriendlyDefeatDealBase
#// LAW_056 Cassian Andor (4/4) — When a friendly unit's attack ends: if the defending unit was defeated,
#// deal 2 damage to a base. P1's SOR_046 attacks and kills SOR_128; Cassian deals 2 to P2's base.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2BASEDMG:2

---

# CassianHimselfDefeatsDealBase
#// LAW_056 Cassian Andor — Cassian himself is a friendly unit, so when HE attacks and the defending unit
#// is defeated his own trigger fires. Cassian (4/4) attacks and kills SOR_128 (3/1), then deals 2 to base.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# NoDefeat_NoTrigger
#// LAW_056 Cassian Andor — if the defending unit is NOT defeated, no base damage. SOR_095 (3/3) attacks
#// SOR_164 Wampa (4/5): the Wampa survives and the Marine is defeated, so Cassian does nothing.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# EnemyAttackDefeats_NoTrigger
#// LAW_056 Cassian Andor — the trigger is only for a FRIENDLY unit's attack. An enemy unit attacking and
#// defeating one of P1's units does not fire Cassian. P2's SEC_080 (3/3) kills P1's SOR_128 (3/1); no base dmg.

## GIVEN
CommonSetup: grk/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: LAW_056:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# FriendlyAttackToBase_NoTrigger
#// LAW_056 Cassian Andor — attacking a base (no defending unit) does not defeat a unit, so Cassian adds
#// nothing beyond combat. SOR_095 (3/3) attacks P2's base directly: base takes only the 3 combat damage.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3

---

# FriendlyLeaderUnitAttackDefeats_Triggers
#// LAW_056 Cassian Andor — a deployed friendly LEADER unit is a friendly unit too: when ITS attack ends
#// and the defending unit was defeated, Cassian deals the 2 to a base. P1's deployed grk leader unit
#// (SOR_010 Darth Vader, 5/8, ground index 1 after seeded Cassian at 0) attacks and kills SOR_128 (3/1);
#// Cassian adds 2 to P2's base (the lone opponent's base — the base pick auto-resolves in 1v1). Vader's
#// own deployed On Attack ("you may deal 2 damage to a unit") is declined with '-' to isolate Cassian's 2.
#// COVERAGE: offer=N/A in 1v1 (the base pick is an opponent choice with a single legal option, so it
#//           auto-resolves — asserted via the damage landing with no pending decision; a multi-opponent
#//           offer is a Twin Suns-only surface) · reqboundary=FriendlyDefeatDealBase (the attack-end
#//           check reads the defender's fate recorded during the attack, across the combat resolution) ·
#//           control=N/A (the trigger keys off "a friendly unit's attack" at attack time; no mid-attack
#//           control handoff is reachable in this fixture family) · boundary=NoDefeat_NoTrigger +
#//           CassianHimselfDefeatsDealBase (survive-vs-die pair) · decline=N/A (the damage is mandatory)

## GIVEN
CommonSetup: grk/bgw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION
