# OnAttackExpSharedTrait
#// LAW_152 C-3PO (1/4) — On Attack: you may give an Experience token to another non-leader unit that
#// shares a Trait with a friendly leader. Leader Luke (Force,Rebel); SOR_095 (Rebel,Trooper) shares Rebel.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# OnAttackExpDecline
#// LAW_152 C-3PO — On Attack ability is optional ("you may"): decline -> no Experience token given to the
#// trait-sharing unit (SOR_095 stays with 0 upgrades).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# OfferIncludesEnemyUnits
#// LAW_152 C-3PO — "another non-leader unit" is NOT friendly-only: an ENEMY unit that shares a
#// Trait with YOUR leader is a legal recipient. P1's leader Luke (Force,Rebel, undeployed); P1 has
#// C-3PO (idx 0) and SOR_046 (idx 1, Rebel); P2 has SOR_095 (Rebel). C-3PO attacks the base and
#// the pick is left PENDING as the assertion: both the friendly SOR_046 and the enemy SOR_095 are
#// offered (C-3PO itself is excluded as the attacker, the leaders as leaders).
#//
#// COVERAGE: offer=this section (both-sides pool pinned pending) · decline=OnAttackExpDecline
#//           (PASS gives no token) · control=N/A (the ability has no control-change clause; the
#//           token simply lands on the chosen unit) · boundary pair=OfferIncludesEnemyUnits vs
#//           OnAttackExpSharedTrait (offer pinned vs resolution applied) ·
#//           reqboundary=EnemyUnitReceivesTheToken (the pick is answered on a later request than
#//           the attack that queued it).

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# EnemyUnitReceivesTheToken
#// LAW_152 C-3PO — resolving the previous section's offer onto the ENEMY unit: P2's SOR_095
#// (Rebel, shares Luke's Rebel trait) receives the Experience token (3/3 -> 4/4) while P1's own
#// SOR_046 gets nothing.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# GrantedLeaderTrait_FulcrumRebel_EnablesShare
#// "Shares a Trait with a friendly leader" must read the leader's LIVE traits, not printed ones.
#// Deployed Vader (Force/Imperial/Sith) wears LAW_150 Fulcrum, gaining Rebel — so the Rebel marine
#// SOR_095 now shares a trait with a friendly leader and is offered. Enemy Wampa (Creature) shares
#// nothing. Offer asserted while pending; exactly the marine.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 2:LAW_150
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:SOR_010
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1SELECTABLEEXACT:myGroundArena-1

---

# DarksaberLeaderUnit_CountsAsAFriendlyLeader
#// A unit made a LEADER UNIT by ASH_135 The Darksaber counts as "a friendly leader". P1's zone leader
#// (Cad Bane — Underworld/Bounty Hunter) shares nothing with any candidate; Yoda wearing the Darksaber
#// is a leader unit whose Force is shared by enemy LOF_231 Darth Tyranus — exactly Tyranus is offered
#// (the enemy marine shares nothing; Yoda himself is now a leader, excluded as a recipient).

## GIVEN
CommonSetup: ggk/rrk/{myLeader:ASH_011}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_045:1:0
WithP1GroundArenaUpgrade: 1:ASH_135
WithP2GroundArena: LOF_231:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0

---

# PhaseTraitStrip_RemovesTheShare
#// The RECIPIENT side must also read live traits. P2's LOF_033 Nameless Terror attacks first: each
#// enemy (P1) unit loses the Force trait this phase. P1's C-3PO then attacks: leader SOR_005 Luke
#// (Force/Rebel) no longer shares Force with the stripped Yoda; the Rebel marine still shares Rebel —
#// the offer is exactly the marine, proving the strip removed Yoda and nothing else.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:SOR_005}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_033:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>Drain

## EXPECT
P1SELECTABLEEXACT:myGroundArena-2
