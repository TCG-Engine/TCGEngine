# Deploy_BelowThreshold_NoOp
#// SOR_006 Emperor Palpatine — Epic Action: "If you control 8 or more resources, deploy
#// this leader." With only 7 resources the condition is unmet, so DeployLeader is a no-op:
#// the leader stays in the leader zone, ready, with the epic action still available.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1LEADER:READY
P1GROUNDARENACOUNT:0
P1NODECISION

---

# LeaderAbility_DealDamageDrawCard
#// SWUSim Replay Schema
Palpatine leader ability — pay 1 resource, defeat friendly, deal 1 damage to a unit

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# LeaderAction_NoFriendlyUnit_Unaffordable
#// SOR_006 Emperor Palpatine — Leader Action costs [1 resource, exhaust, defeat a friendly
#// unit]. With 8 resources but no friendly unit, the defeat-a-friendly-unit cost cannot be
#// paid, so the action is a no-op: leader stays ready, no resource spent, nothing queued.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:8
P1NODECISION

---

# OnAttack_SacrificeNo
#// SWUSim Replay Schema
Palpatine OnAttack — decline sacrifice, no bonus damage, normal combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# OnAttack_SacrificeYes
#// SWUSim Replay Schema
Palpatine OnAttack — sacrifice friendly unit, deal 1 damage, proceed to combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_006
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1

---

# OnAttack_SacrificeYes_Useless
#// SWUSim Replay Schema
Palpatine OnAttack — sacrifice friendly unit, deal 1 damage, proceed to combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_006
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1

---

# WhenDeployed_NoDamagedUnit
#// SWUSim Replay Schema
Palpatine WhenDeployed — no damaged units, no steal trigger fires

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION

---

# WhenDeployed_StealDamagedUnit
#// SWUSim Replay Schema
Palpatine WhenDeployed — take control of a damaged non-leader unit (auto-resolve single target)

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:2
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
