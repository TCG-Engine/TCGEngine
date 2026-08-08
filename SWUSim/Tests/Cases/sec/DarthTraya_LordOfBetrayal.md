# OnAttack_ReadyFriendlyLeader
#// SEC_188 Darth Traya (Ground, 2/5) — On Attack: you may ready a non-unit (undeployed) leader. P1's
#//   leader starts exhausted; SEC_188 attacks P2's base and P1 chooses to ready their own leader.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You

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
- P1>AnswerDecision:Opponent

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
- P1>AnswerDecision:Opponent

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
