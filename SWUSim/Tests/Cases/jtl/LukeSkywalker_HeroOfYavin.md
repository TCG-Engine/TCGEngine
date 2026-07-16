# OnAttack_Fighter_Deals3
#// JTL_012 deployed as a PILOT on a Fighter host (SOR_237) — the host gains "On Attack: You may deal
#// 3 damage to a unit." Host attacks the base; deals 3 to the enemy JTL_069 (4/7 -> 3 damage).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3

---

# OnAttack_NonFighter_NoGrant
#// JTL_012's grant is gated "If it's a Fighter". On a non-Fighter host (JTL_069, Capital Ship) the
#// On Attack does NOT fire — no decision, the enemy TIE is undamaged.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# AttackedFighter_DealsUnit
#// JTL_012 Luke Skywalker (leader) — Action [Exhaust]: If you attacked with a Fighter unit this phase,
#// deal 1 damage to a unit. P1's X-Wing (SOR_237, a Fighter) attacks P2's base, then Luke's action deals
#// 1 to the enemy SOR_095.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# NoFighterAttack_NoOp
#// JTL_012 Luke Skywalker (leader) — the damage only happens if you attacked with a Fighter this phase.
#// Here P1 never attacked, so the action does nothing (leader still exhausts), nothing is damaged, and no
#// decision is pending. Gate test.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
