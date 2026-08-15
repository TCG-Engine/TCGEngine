# OnAttack_BuffsAnotherUnitInArena
#// JTL_011 Major Vonreg deployed as a PILOT — the host gains "On Attack: You may give another unit in
#// this arena +1/+0 for this phase." Host (SOR_225 @0) attacks the base; buffs the other friendly
#// space unit JTL_069 (4/7 -> 5/7).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_011;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: [SOR_225:1:0 JTL_069:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:1:POWER:5

---

# PlayVehicle_BuffsAnother
#// JTL_011 Major Vonreg (leader) — Action [Exhaust]: Play a Vehicle unit from your hand (paying its
#// cost). If you do, give another unit +1/+0 for this phase. P1 plays SOR_225 (TIE/ln, Villainy Vehicle,
#// cost 1) and then buffs the OTHER unit SEC_080 (3/3 → 4/3); the just-played TIE is excluded.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# PlayVehicle_NoOtherUnit_NoBuff
#// JTL_011 Major Vonreg (leader) — the +1/+0 is given to ANOTHER unit. With no other unit in play after
#// the Vehicle enters, the buff has no target and fizzles: the played TIE keeps its printed 2 power.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:POWER:2
P1LEADER:EXHAUSTED

---

# DeployedAsUnit_NoOnAttackGrant
#// JTL_011 Major Vonreg — the "On Attack: buff another unit" is a deploy-AS-A-PILOT grant. Deployed as a
#// normal ground UNIT, Vonreg has no On Attack ability: attacking gives no buff and no decision pends.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myLeaderDeployed:true;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:2
P1NODECISION

---

# SimulateRequestBoundary_PilotOnAttackBuff
#// JTL_011 Major Vonreg deployed as a PILOT — the host's granted "On Attack: you may give another unit in
#// this arena +1/+0 for this phase" opens a decision mid-attack, and the phase-duration buff is applied
#// only after the answer. In production that answer arrives in a fresh process, so the pending grant
#// context must come from the serialized gamestate. Mirrors OnAttack_BuffsAnotherUnitInArena with a
#// request boundary before the answer.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_011;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: [SOR_225:1:0 JTL_069:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:1:POWER:5

---

# Offer_PlayPoolIsVehicleUnitsInHandOnly
#// JTL_011 Major Vonreg — "Action [Exhaust]: Play a VEHICLE UNIT from your hand (paying its cost)."
#// The hand offer must filter on both the Vehicle trait and the Unit card type. Hand (in order):
#// SOR_225 TIE/ln (Vehicle unit, cost 1 — eligible), SOR_249 Frontier AT-RT (Vehicle unit, cost 4 —
#// eligible), SOR_063 Cloud City Wing Guard (a cost-3 Trooper unit — a UNIT but not a Vehicle) and
#// SOR_120 Academy Training (an UPGRADE, not a unit at all). With 4 resources both Vehicles are
#// affordable, so the two exclusions are driven purely by trait/type, not by cost.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_225 SOR_249 SOR_063 SOR_120]
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Play_a_Vehicle_unit_from_your_hand
P1SELECTABLEEXACT:myHand-0&myHand-1
