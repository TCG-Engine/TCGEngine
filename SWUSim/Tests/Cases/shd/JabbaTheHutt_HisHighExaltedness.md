# BountyReward_NextUnitCheaper
#// SHD_006 Jabba the Hutt — collecting the granted Bounty. P1 Jabba bounties the enemy Battlefield Marine
#// (SOR_095, 3/3); P1's Industrious Team (LAW_124, 4/7) attacks and defeats it; P1 — the opponent of the
#// bountied unit's controller (CR 13.f) — is offered the Bounty and collects it, arming "the next unit you
#// play this phase costs 1 resource less." P1 then plays Imperial Dark Trooper (SEC_080, cost 2). With the
#// discount it costs 1, so 1 of P1's 2 ready resources remains (without the discount it would be 0).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1

---

# Deployed_Action_GrantsBounty
#// SHD_006 Jabba the Hutt (deployed leader unit) — "Action [Exhaust]: Choose a unit. For this phase it
#// gains 'Bounty - The next unit you play this phase costs 2 resources less.'" The deployed Jabba unit
#// uses its Action and bounties the enemy Battlefield Marine, which gains the Bounty keyword; Jabba exhausts.
#// (Same grant mechanism as the front side; the deployed reward pays 2 instead of 1.)

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# FrontAction_GrantsBounty
#// SHD_006 Jabba the Hutt (leader front) — "Action [Exhaust]: Choose a unit. For this phase it gains
#// 'Bounty - The next unit you play this phase costs 1 resource less.'" P1 Jabba bounties the enemy
#// Battlefield Marine (SOR_095). The marine gains the Bounty keyword (the badge shows) and Jabba exhausts.
#// Only one unit is in play, so the "choose a unit" auto-resolves to it (PASSPARAMETER, no AnswerDecision).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1LEADER:EXHAUSTED

---

# FrontGrant_ExpiresNextPhase
#// SHD_006 Jabba the Hutt (leader front) — the granted Bounty is "for this phase". After Jabba bounties
#// the enemy Battlefield Marine, the action phase ends (P1 passes; P2 auto-passes under P1OnlyActions),
#// RegroupPhaseStart runs SWUExpireTurnEffects('phase'), and the marine no longer has the Bounty keyword.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Bounty

---

# WhenDeployed_Capture
#// SHD_006 Jabba the Hutt — "When Deployed: Another friendly unit captures an enemy non-leader unit."
#// P1 deploys Jabba (Epic Action, 7+ resources). On deploy, P1's Industrious Team (LAW_124) — the only
#// friendly non-Jabba unit — captures the enemy Battlefield Marine (SOR_095), the only enemy non-leader
#// unit. Both picks auto-resolve. The marine leaves P2's arena (captured as a face-down subcard on LAW_124).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
