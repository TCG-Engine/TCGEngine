# Deploy_OnAttack_BuffsAnother
#// JTL_007 Admiral Holdo (deployed leader unit) — On Attack: You may give ANOTHER Resistance unit (or a
#// unit with a Resistance upgrade) +2/+2 this phase. P1 deploys Holdo (free epic, 6-resource threshold),
#// attacks P2's base, and buffs the other Resistance unit JTL_099 (2/1 → 4/3). "Another" excludes Holdo.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# Deploy_OnAttack_Decline
#// JTL_007 Admiral Holdo (deployed leader unit) — the On Attack buff is optional ("You may"). P1 deploys
#// Holdo, attacks, and DECLINES (AnswerDecision:-): JTL_099 keeps its printed 2/1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:1
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# LeaderAction_BuffExpiresNextPhase
#// JTL_007 Admiral Holdo (leader) — the +2/+2 lasts only "for this phase". After P1 buffs JTL_099 and
#// both players pass (action phase ends → regroup runs the centralized turn-effect expiry), the buff is
#// gone and JTL_099 is back to its printed 2/1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:1

---

# LeaderAction_BuffsResistanceUnit
#// JTL_007 Admiral Holdo (leader) — Action [1 resource, Exhaust]: Give a Resistance unit (or a unit
#// with a Resistance upgrade) +2/+2 for this phase. The only target is JTL_099 (Resistance, 2/1), which
#// becomes 4/3.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoResource_NoOp
#// JTL_007 Admiral Holdo (leader) — the action costs 1 resource. With 0 ready resources the cost can't
#// be paid: the action never starts, Holdo stays READY, the Resistance unit is not buffed, and no
#// decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1LEADER:READY
P1NODECISION

---

# LeaderAction_UnitWithResistanceUpgrade
#// JTL_007 Admiral Holdo (leader) — the buff also targets a unit with a RESISTANCE upgrade on it (not
#// just a Resistance unit). Host JTL_069 Munificent Frigate (Separatist, 4/7) carries a Resistance pilot
#// upgrade JTL_046 (+2/+2 → 6/9); Holdo's +2/+2 makes it 8/11. Proves the "Resistance upgrade" clause.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:11
P1LEADER:EXHAUSTED
