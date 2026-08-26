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

---

# TwinSuns_DeployAsUnit_DeploysTHISLeaderNotLeaderZero
#// ⚠ REPORTED BUG (2026-08-25): "Vonreg JTL leader was undeployable" in Twin Suns.
#//
#// JTL_011 is one of the ten PILOT leaders (leaderCanDeployAsUpgradeData), so when an eligible Vehicle is
#// on the board SWUDeployLeader does NOT deploy immediately — it queues an OPTIONCHOOSE "Unit&Pilot" plus
#// a LEADER_DEPLOY_CHOICE continuation, and returns. The continuation then re-enters SWUDeployLeader.
#//
#// THE BUG: SWUDeployLeader's 4th parameter is $leaderIndex, and BOTH re-entry points drop it —
#// LEADER_DEPLOY_CHOICE calls SWUDeployLeader($player, 'UnitDirect') and LEADER_DEPLOY_PILOT calls
#// SWUDeployLeader($player, 'Pilot', $hostMz), each defaulting $leaderIndex to 0. The queued param
#// "LEADER_DEPLOY_CHOICE|{$cardID}" never carried the index either, so it is unrecoverable downstream.
#//
#// In Premier there is only ever one leader, so index 0 is always right and this was invisible for the
#// engine's whole prior life. In Twin Suns each seat has TWO. Deploy the SECOND leader (index 1) and the
#// continuation deploys leader index 0 instead — and when leader 0 is already deployed, SWUDeployLeader's
#// `if ($leader->Deployed) return;` guard makes the whole action a SILENT no-op. That is exactly what
#// "undeployable" looks like from the seat: click deploy, choose Unit, nothing happens.
#//
#// Affects all ten pilot leaders (JTL_001/003/006/008/009/011/012/015/017/018), not just Vonreg.
#// ⚠ The eligible Vehicle is load-bearing FIXTURE: with no Vehicle in play the choose-one gate never
#// fires, SWUDeployLeader falls straight through with the correct $leaderIndex, and the bug hides.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:SOR_002; myLeader2:JTL_011}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>DeployLeader:1
- P1>AnswerDecision:Unit

## EXPECT
P1LEADER1DEPLOYED:true
P1LEADER0DEPLOYED:false

---

# TwinSuns_DeployAsPilot_AttachesTHISLeaderNotLeaderZero
#// The Pilot half of the same defect. Choosing "Pilot" with exactly ONE eligible Vehicle auto-attaches
#// via LEADER_DEPLOY_CHOICE -> SWUDeployLeader($player, 'Pilot', $vehicles[0]) — again with $leaderIndex
#// defaulted to 0, so seat 1's FIRST leader is the one that flips to deployed and rides the X-Wing.
#//
#// Asserting the upgrade count as well as the deployed flags: a fix that passed the index but attached
#// nothing would still read as "leader 1 deployed" without the leader actually being on the ship.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:SOR_002; myLeader2:JTL_011}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>DeployLeader:1
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER1DEPLOYED:true
P1LEADER0DEPLOYED:false
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
