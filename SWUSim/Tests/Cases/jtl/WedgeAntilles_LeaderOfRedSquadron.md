# OnAttack_NextPilotUnitCostsLess
#// JTL_008 Wedge deployed as a PILOT — the host gains "On Attack: The next Pilot card you play this
#// phase costs 1 less (includes Piloting costs)." After the host attacks (arming the discount), P1
#// plays JTL_046 (a Pilot, cost 2) AS A UNIT for 1: 10 ready resources -> 9.

## GIVEN
CommonSetup: bgw/rrk/{myResources:10;myLeader:JTL_008;myLeaderDeployedPilot:true;myhandCardIds:JTL_046}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Unit

## EXPECT
P1RESAVAILABLE:9

---

# LeaderAction_NoVehicle_Fizzle
#// JTL_008 Wedge Antilles (leader) — with no friendly Vehicle in play there is no valid Piloting host,
#// so the pilot in hand is not playable via Piloting and the action fizzles: the leader exhausts, the
#// pilot stays in hand, and no card is attached.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_108
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_PlayPilotOneLess
#// JTL_008 Wedge Antilles (leader) — Action [Exhaust]: Play a card from your hand using Piloting. It
#// costs 1 resource less. P1 plays JTL_108 (pure Pilot, Piloting cost 2, Command — covered by Wedge) as
#// an upgrade onto the Munificent Frigate for 2 − 1 = 1 resource, leaving 0. With only 1 ready resource,
#// this play is ONLY possible because of the −1 discount (full cost 2 would be unaffordable).

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Hand: JTL_108
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# Deploy_AsGroundUnit_Stats
#// JTL_008 Wedge Antilles — deployed as a normal ground unit (no friendly Vehicle → DeployLeader skips the
#// Unit/Pilot choice). Wedge enters the ground arena as a 3/6 leader unit.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_008
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:DEPLOYED

---

# Deploy_AsPilot_DoesNotDiscountNonPilot
#// JTL_008 Wedge deployed as a PILOT grants the host "On Attack: the next PILOT card you play this phase
#// costs 1 less." That discount applies ONLY to Pilot cards. After the host attacks (arming the effect),
#// P1 plays SOR_237 Alliance X-Wing (a Vehicle, NOT a Pilot; cost 2, Heroism on-aspect) — it is NOT
#// discounted, so 10 ready resources drop by the full 2 → 8 (a discount would leave 9).

## GIVEN
CommonSetup: bgw/rrk/{myResources:10;myLeader:JTL_008;myLeaderDeployedPilot:true;myhandCardIds:SOR_237}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:8

---

# Deploy_AsGroundUnit_AttackDoesNotDiscountPilot
#// JTL_008 Wedge — the "next Pilot costs 1 less" effect is a PILOT-deploy grant on the host, NOT something
#// Wedge does when deployed as a normal GROUND UNIT. Wedge is deployed as a ground unit and attacks P2's
#// base; P1 then plays a Pilot (JTL_046 Paige Tico, cost 2) as a unit — it is NOT discounted (full cost 2),
#// so 10 ready resources → 8.

## GIVEN
CommonSetup: bgw/rrk/{myResources:10;myLeader:JTL_008;myLeaderDeployed:true;myhandCardIds:JTL_046}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:8
