# DeployAsPilot_ShieldDifferentArena
#// JTL_003 Lando Calrissian (leader) — "When deployed as an upgrade: You may give a Shield token to a
#// unit in a different arena." Lando deploys as a Pilot onto a space Vehicle (SOR_237), then shields a
#// unit in the GROUND arena (the only other-arena unit, SOR_095).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# LeaderAction_NoGroundUnit_NoShield
#// JTL_003 Lando Calrissian (leader) — the Shield rider requires controlling BOTH a ground and a space
#// unit after the play. Here P1 controls no units, then plays a space unit (SOR_237); it controls a
#// space unit but no ground unit, so no Shield is granted. Proves the conjunctive condition.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_NoResource_NoOp
#// JTL_003 Lando Calrissian (leader) — the action costs 1 resource. With 0 ready resources the cost
#// can't be paid, so the action never starts: Lando stays READY (action not spent), the hand unit is
#// not played, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P1HANDCOUNT:1
P1SPACEARENACOUNT:0

---

# LeaderAction_PlaysUnit_GivesShield
#// JTL_003 Lando Calrissian (leader) — Action [1 resource, Exhaust]: Play a unit from your hand (paying
#// its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit.
#// P1 already controls a ground unit (SOR_095). It pays 1 (leader) + 2 (SOR_237 Alliance X-Wing, Heroism
#// covered by Lando) = 3 → 0. Now controlling ground + space, it gives a Shield to the X-Wing.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
