# Deployed_OnAttack_ExpToImperial
#// SOR_007 Grand Moff Tarkin — deployed leader unit (2/7) On Attack: You may give an Experience
#// token to ANOTHER Imperial unit. Deployed Tarkin (the only ground unit) attacks the base; on
#// YES the only other Imperial unit — SOR_225 (2/3, space) — auto-receives +1/+1 (→ 3/4). The
#// base takes Tarkin's 2 power.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0     # another Imperial unit (space) — Experience recipient

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P2BASEDMG:2
P1LEADER:EPICUSED

---

# LeaderAction_ExpToImperial
#// SOR_007 Grand Moff Tarkin (leader) — Action [1 resource, exhaust]: Give an Experience token
#// to an Imperial unit. P1 uses the leader action: pays 1 resource (2 → 1 ready), the leader
#// exhausts, and the only Imperial unit (SOR_229, 3/3) auto-receives +1/+1 (→ 4/4).

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_229:1:0    # Imperial unit — Experience recipient

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# LeaderAction_NoResource_NoOp
#// SOR_007 Grand Moff Tarkin — the leader Action costs 1 resource. With 0 ready resources it
#// is a full no-op: the leader stays READY (action not spent), the Imperial unit gets no
#// Experience, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_229:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION
