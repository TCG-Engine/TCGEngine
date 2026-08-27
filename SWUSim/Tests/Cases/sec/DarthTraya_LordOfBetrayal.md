# OnAttack_ReadyFriendlyLeader
#// SEC_188 Darth Traya (Ground, 2/5) — On Attack: you may ready a non-unit (undeployed) leader. P1's
#//   leader starts exhausted; SEC_188 attacks P2's base and P1 chooses to ready their own leader.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:P1

## EXPECT
P2BASEDMG:2
P1LEADER:READY

---

# OnAttack_ReadyEnemyLeader
#// SEC_188 Darth Traya — "a non-unit leader" has no "friendly" qualifier, so an ENEMY undeployed leader is
#//   a legal ready target too. Both leaders start exhausted; choosing Opponent readies P2's leader while
#//   P1's own stays exhausted.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0;theirLeader:SOR_005:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:P2

## EXPECT
P2BASEDMG:2
P2LEADER:READY
P1LEADER:EXHAUSTED

---

# OnAttack_MayDecline
#// SEC_188 Darth Traya — the ability is "you may", so it can be declined: P1's exhausted leader stays
#//   exhausted when the ready is passed.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Pass

## EXPECT
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# DeployedFriendlyLeaderUnitIsNotAValidReadyTarget
#// SEC_188 Darth Traya — she readies "a non-unit leader", i.e. one still on its leader card. A DEPLOYED
#// friendly leader is a leader UNIT and must not be offered. P1's leader is deployed and exhausted while
#// P2's is undeployed and exhausted, so the only choice is the opponent's: P2's leader readies and P1's
#// deployed leader unit stays exhausted.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0:1;theirLeader:SOR_014:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:P2

## EXPECT
P2BASEDMG:2
P1LEADER:DEPLOYED
P1LEADER:EXHAUSTED
P2LEADER:READY

---

# BothLeadersDeployed_NoReadyTargetAtAll
#// SEC_188 Darth Traya — with EVERY leader deployed there is no non-unit leader anywhere, so the
#// optional ready has nothing to offer and the attack resolves with no dangling decision.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0:1;theirLeader:SOR_014:0:1}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1LEADER:EXHAUSTED
P2LEADER:EXHAUSTED
P1NODECISION

---

# FourSeats_OffersAndReadiesAFARSeatsLeader
#// SEC_188 at FOUR seats — "ready A NON-UNIT LEADER" carries no friendly/enemy qualifier, so every seat
#// holding an exhausted, undeployed leader is a candidate. The picker used to be a literal You/Opponent
#// pair built from OtherPlayer($p), so above two seats only seat 2's leader could ever be offered.
#// BOTH P2's and P4's leaders are exhausted here and P1 names P4: the legacy shape fails on the OFFER
#// (its pool is [P2&Pass] — the message names the missing seat), and any "collapse the pick onto seat 2"
#// applier fails on P2LEADER:EXHAUSTED. P1's and P3's leaders start ready, so they are correctly absent
#// from the pool rather than merely unchosen.
#// ⚠ Needs the OPTIONCHOOSE pool validator to discriminate on the offer — without it an answer naming a
#// seat the picker never listed still resolves. See the plan doc.

## GIVEN
CommonSetup: yyk/rrk/{theirLeader:SOR_016:0}
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP4Leader: SOR_016:0

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P2BASEDMG:2
P4LEADER:READY
P4LEADER:NOTDEPLOYED
P2LEADER:EXHAUSTED
