# BountyReward_NextUnitCheaper
#// COVERAGE: offer=DeployedAction_OfferIncludesJabbaHimself (the Action's "choose a unit" pool — both
#//           sides, Jabba himself in) · WhenDeployed_CaptorOfferExcludesJabbaHimself ("ANOTHER friendly
#//           unit" — Jabba out) · WhenDeployed_CaptiveOfferIsEnemyNonLeaderUnitsInBothArenas ("an enemy
#//           NON-LEADER unit" — both enemy arenas in, P2's deployed leader out); all three left pending
#//           and read with P1SELECTABLEEXACT ·
#//           boundary=the two discounts, front 1 (BountyReward_NextUnitCheaper) vs deployed 2
#//           (DeployedDiscount_TwoResourcesLess); and the discount's "NEXT unit only" pair —
#//           FrontDiscount_AppliesToTheNextUnitOnly / DeployedDiscount_AppliesToTheNextUnitOnly (first
#//           unit discounted, second at full price) ·
#//           reqboundary=the two independent phase expiries, each written during one action and read on a
#//           LATER action after crossing into the next action phase: the BOUNTY GRANT expiring
#//           (FrontGrant_ExpiresNextPhase, DeployedGrant_ExpiresAtEndOfPhase_NoCollectLater — the later
#//           defeat raises no collect offer at all) and the collected DISCOUNT expiring
#//           (FrontDiscount_ExpiresAtEndOfPhase, DeployedDiscount_ExpiresAtEndOfPhase) ·
#//           control=N/A — the grant is a phase-duration turn-effect on the CHOSEN unit and the reward is
#//           paid to whoever collects (the opponent of that unit's controller, read live at defeat time);
#//           no seat is stored on either side, and the discount is armed on the collector's own play ·
#//           decline=N/A on both Jabba clauses — the Action's "choose a unit" and the When Deployed capture
#//           are both mandatory with no "you may"; the only refusable step is the shared Bounty collect
#//           YES/NO, whose decline branch lives in UnlicensedHeadhunter.md::DeclineTheBounty_NoHealing
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

---

# FrontDiscount_AppliesToTheNextUnitOnly
#// SHD_006 Jabba the Hutt (front) — "The NEXT unit you play this phase costs 1 resource less." The tail of
#// BountyReward_NextUnitCheaper: the discount is spent by the first unit played after the collect, and the
#// one played straight after it pays full price again. P1 grants the Bounty to the enemy marine, Industrious
#// Team defeats it, P1 collects, then plays two Imperial Dark Troopers (SEC_080, cost 2) back to back out
#// of 5 resources — the first costs 1, the second 2, so 2 of the 5 are still ready.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: [SEC_080 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:2

---

# FrontDiscount_ExpiresAtEndOfPhase
#// SHD_006 Jabba the Hutt (front) — the collected reward is also phase-scoped: "the next unit you play THIS
#// PHASE costs 1 resource less." P1 grants, defeats and collects, but plays nothing before passing. Crossing
#// into the next action phase the discount is gone, so the Imperial Dark Trooper costs its full 2 out of 5
#// (3 ready left) instead of the 1 it would have cost in the phase the bounty was collected.
#// Both decks are seeded past the regroup draws — drawing from an empty deck deals 3 to that base.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:3

---

# DeployedDiscount_TwoResourcesLess
#// SHD_006 Jabba the Hutt (deployed) — the deployed Action grants a Bounty worth 2, not the front's 1.
#// The deployed Jabba unit bounties the enemy marine; Industrious Team defeats it; P1 collects and the next
#// unit played costs 2 less — Imperial Dark Trooper (cost 2) becomes FREE, leaving all 5 resources ready.
#// (A deployed leader seats at the END of the ground arena, so Jabba is at index 1 behind Industrious Team.)

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: [SEC_080 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:5

---

# DeployedDiscount_AppliesToTheNextUnitOnly
#// SHD_006 Jabba the Hutt (deployed) — the deployed twin of FrontDiscount_AppliesToTheNextUnitOnly. Same
#// flow as DeployedDiscount_TwoResourcesLess, but P1 plays a SECOND Imperial Dark Trooper straight after
#// the free one: the discount was spent by the first, so the second pays its full 2 and 3 of the 5
#// resources are still ready.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: [SEC_080 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:3

---

# DeployedAction_OfferIncludesJabbaHimself
#// SHD_006 Jabba the Hutt (deployed) — "Choose A UNIT" with no "another" and no controller qualifier, so
#// the deployed Jabba is a legal target for his own Action alongside every other unit on the board. The
#// pick is left pending to assert the pool: Industrious Team (index 0), Jabba himself (index 1, deployed
#// leaders seat last) and the enemy marine.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-1

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# DeployedGrant_ExpiresAtEndOfPhase_NoCollectLater
#// SHD_006 Jabba the Hutt (deployed) — the deployed Action's grant is phase-scoped exactly like the front's
#// (FrontGrant_ExpiresNextPhase asserts the keyword is gone; this asserts the CONSEQUENCE). Jabba bounties
#// the enemy marine, P1 passes without cashing it, and the phase ends. In the NEXT action phase Industrious
#// Team defeats that same marine and no collect offer is raised at all — P1NODECISION is the proof the
#// granted Bounty really expired rather than merely losing its badge.
#// Both decks are seeded past the regroup draws — drawing from an empty deck deals 3 to that base.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION

---

# DeployedDiscount_ExpiresAtEndOfPhase
#// SHD_006 Jabba the Hutt (deployed) — the deployed twin of FrontDiscount_ExpiresAtEndOfPhase. P1 grants,
#// defeats and collects the 2-resource discount, then plays nothing before the phase ends. In the next
#// action phase the Imperial Dark Trooper pays its full 2 out of 5 (3 ready left) rather than being free
#// as it was in DeployedDiscount_TwoResourcesLess.
#// Both decks are seeded past the regroup draws — drawing from an empty deck deals 3 to that base.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:3

---

# WhenDeployed_CaptorOfferExcludesJabbaHimself
#// SHD_006 Jabba the Hutt — "When Deployed: ANOTHER friendly unit captures an enemy non-leader unit."
#// The captor pick is left pending to assert the pool: the two pre-seated friendly ground units are in and
#// the just-deployed Jabba (who seats last, at index 2) is out — he cannot capture for himself.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WhenDeployed_CaptiveOfferIsEnemyNonLeaderUnitsInBothArenas
#// SHD_006 Jabba the Hutt — the second half of the When Deployed capture. P1 has a single friendly captor
#// so that pick auto-resolves, leaving the CAPTIVE pool pending: "an enemy NON-LEADER unit" spans both
#// enemy arenas (the ground marine and the space frigate) but excludes P2's deployed leader unit, which
#// seats at the end of P2's ground arena.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
